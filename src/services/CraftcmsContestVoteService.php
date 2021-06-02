<?php
/**
 * CraftCMS Contests plugin for Craft CMS 3.x
 *
 * This is a plugin that allows you to run contests with voting in your CraftCMS site
 *
 * @link      https://the-refinery.io
 * @copyright Copyright (c) 2021 The Refinery
 */

namespace therefinery\craftcmscontests\services;

use therefinery\craftcmscontests\CraftcmsContests;
use therefinery\craftcmscontests\records\CraftcmsContestRecord;
use therefinery\craftcmscontests\records\CraftcmsContestVoteRecord;
use therefinery\craftcmscontests\models\CraftcmsContestModel;
use therefinery\craftcmscontests\models\CraftcmsContestVoteModel;

use Craft;
use craft\helpers\Json;
use craft\db\Query;
use craft\base\Component;

/**
 * @author    The Refinery
 * @package   CraftcmsContests
 * @since     1.0.0
 */
class CraftcmsContestVoteService extends Component
{
    public function saveVote(CraftcmsContestVoteRecord $vote, CraftcmsContestRecord $contest) {
        if(!$contest) {
            Craft::error("ERROR: Incoming vote with contestId='{$vote->contestId}': Contest not found. ", "contestify");
            return array(
                'success' => false,
                'message' => "Contest not found."
            );
        }

        if(!$contest->enabled) {
            return array(
                'success' => false,
                'message' => "Contest '{$contest->name}' is not currently active.",
            );
        }

        if(!$contest->categories) {
            Craft::error("ERROR: Incoming vote with contestId='{$contest->id}', contestName='{$contest->name}': Contest does not have any categories.", "contestify");
            return array(
                'success' => false,
                'message' => "Contest '{$contest->name}' does not have any categories.",
            );
        }

        if(!in_array($vote->categoryId, json_decode($contest->categories, true))) {
            Craft::error("ERROR: Incoming vote with contestId='{$contest->id}', contestName='{$contest->name}': Contest is not associated with category '{$vote->categoryId}'.", "contestify");
            return array(
                'success' => false,
                'message' => "Invalid category for contest.",
            );
        }

        if(!$vote->email) {
            Craft::error("ERROR: Email address not supplied with incoming vote.", "contestify");
            return array(
                'success' => false,
                'message' => "Email address required.",
            );
        }

        // If sessionProtect is enabled, return immediately if the session timestamp stored
        // for a particular contest has not reached the timeout limit set by the contest.
        // This is to prevent a user with a particular session from sending out many votes
        // with different email addresses.
        if($contest->sessionProtect) {
            $sessionContestVoteTimestamp = craft()
                ->httpSession
                ->get("contestify:voteSessionProtectionTimestamp:{$contest->handle}");

            if($sessionContestVoteTimestamp) {
                $epochNow = strtotime("now");
                $epochCreatedPlusTimeout = strtotime(
                    "+{$contest->lockoutLength} {$contest->lockoutFrequency}",
                    $sessionContestVoteTimestamp->getTimestamp()
                );

                if($epochNow < $epochCreatedPlusTimeout) {
                    $category = craft()
                        ->categories
                        ->getCategoryById($vote->categoryId);

                    return array(
                        'success' => false,
                        'message' => "You can only vote once every {$contest->lockoutLength} {$contest->lockoutFrequency} for category '{$category->title}'. Please try again soon.",
                    );
                }
            }
        }

        $voteRecord = new CraftcmsContestVoteRecord();

        // Run validations to make sure a record doesn't exist in the database within the lockout timeframe
        $vote->email = strtolower($vote->email);
        $emailValidation = $this->validateWithinTimeframe('email', $vote->email, $vote->categoryId, $contest);
        $ipValidation = $this->validateWithinTimeframe('ip', $vote->ip, $vote->categoryId, $contest);

        // Return an error message if validations fail
        if($emailValidation) { // TODD: add $ipValidation as check, disabled for testing
            $lockoutLength = $contest->lockoutLength;
            $lockoutFrequency = $contest->lockoutFrequency;
            $lockoutFrequency = $lockoutLength > 1 ? $lockoutFrequency . 's' : $lockoutFrequency;

            return array(
                'success' => false,
                'message' => "You can only vote once every {$lockoutLength} {$lockoutFrequency} for category ID='{$vote->categoryId}'. Please try again soon.",
            );
        }

        // If validation passes, save the vote, return a success message
        if( $vote->save() ) {
            Craft::$app->getSession()->set("contestify:voteSessionProtectionTimestamp:{$contest->handle}", $vote->dateCreated);

            return array(
                'success' => true,
                'message' => 'You vote has successfully been saved.',
            );
        }

        // If vote fails to save, return an error message
        return array(
            'success' => false,
            'message' => 'There was an error saving your vote. Please try again soon.',
        );
    }

    public function getEntryVoteCount($entryId, $contestId) {
        $queryResult = craft()->db->createCommand()
            ->select('count(entryId) as entryCount')
            ->from('contestify_votes')
            ->where(
                array(
                    "contestId" => $contestId,
                    "entryId" => $entryId
                )
            )
            ->group("entryId")
            ->queryAll();

        if (count($queryResult) == 0)
        {
            return 0;
        }
        else
        {
            return $queryResult[0]["entryCount"];
        }
    }

    public function getAllVoteCountsByContestId($contestId) {
        $queryResult = CraftcmsContestVoteRecord::find()
            ->select('count(entryId) as entryCount, entryId')
            ->where(
                array(
                    "contestId" => $contestId
                )
            )
            ->groupBy("entryId")
            ->asArray()
            ->all();

        $map = [];

        // Create a dictionary, where key = entryId and value = number of votes, e.g.
        // {
        //     123 => 1,
        //     124 => 2,
        // }
        //
        // Then afterwards, you can do quick lookups using:
        // $map[123]
        foreach($queryResult as $result) {
            $map[$result["entryId"]] = (int) $result["entryCount"];
        }

        return $map;
    }

    // Checks if a field value exists in the database within the lockout timeframe
    private function validateWithinTimeframe($field, $value, $categoryId, $contest)
    {
        $dateRangeCriteria = $this->dateRangeCriteria($contest);
        $attribs = array($field => $value, 'categoryId' => $categoryId);

        $rows = CraftcmsContestVoteRecord::find()
            ->andWhere(["=", $field, $value])
            ->andWhere(["=", "categoryId", $categoryId])
            ->andWhere($dateRangeCriteria)
            ->one();

        return $rows;
    }

    // Sets up query criteria for records that fall within the specified time frame
    private function dateRangeCriteria($contest)
    {
        $lockoutLength = $contest->lockoutLength;
        $lockoutFrequency = $contest->lockoutFrequency;

        $lockoutFrequency = $lockoutLength > 1 ? $lockoutFrequency . 's' : $lockoutFrequency;

        $datePrev = date('Y-m-d H:i:s', strtotime("-{$lockoutLength} {$lockoutFrequency}"));
        $dateNow = date('Y-m-d H:i:s');
        return ['between', 'dateCreated', $datePrev, $dateNow];
    }
}
