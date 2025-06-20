<?php
/**
 * CraftCMS Contests plugin for Craft CMS 4.x
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
use yii\db\ActiveRecord;

/**
 * @author    The Refinery
 * @package   CraftcmsContests
 * @since     1.0.0
 */
class CraftcmsContestVoteService extends Component
{
    public function saveVote(
        CraftcmsContestVoteRecord $vote,
        CraftcmsContestRecord $contest,
    ): ?array {
        Craft::info("Starting saveVote for contest ID: {$contest->id}, category ID: {$vote->categoryId}", 'craft-cms-contests');
        if (!$contest) {
            Craft::error(
                "ERROR: Incoming vote with contestId='{$vote->contestId}': Contest not found. ",
                "craft-cms-contests",
            );
            return [
                "success" => false,
                "message" => "Contest not found.",
            ];
        }

        if (!$contest->enabled) {
            return [
                "success" => false,
                "message" => "Contest '{$contest->name}' is not currently active.",
            ];
        }

        if (!$contest->categories) {
            Craft::error(
                "ERROR: Incoming vote with contestId='{$contest->id}', contestName='{$contest->name}': Contest does not have any categories.",
                "craft-cms-contests",
            );
            return [
                "success" => false,
                "message" => "Contest '{$contest->name}' does not have any categories.",
            ];
        }

        if (
            !in_array(
                $vote->categoryId,
                json_decode($contest->categories, true),
            )
        ) {
            Craft::error(
                "ERROR: Incoming vote with contestId='{$contest->id}', contestName='{$contest->name}': Contest is not associated with category '{$vote->categoryId}'.",
                "craft-cms-contests",
            );
            return [
                "success" => false,
                "message" => "Invalid category for contest.",
            ];
        }

        if (!$vote->email) {
            Craft::error(
                "ERROR: Email address not supplied with incoming vote.",
                "craft-cms-contests",
            );
            return [
                "success" => false,
                "message" => "Email address required.",
            ];
        }

        // If sessionProtect is enabled, check if the user has already voted in the current period
        Craft::info("Session protection is " . ($contest->sessionProtect ? 'ENABLED' : 'DISABLED'), 'craft-cms-contests');
        if ($contest->sessionProtect) {
            $sessionKey = "craft-cms-contests:voteSessionProtectionTimestamp:{$vote->categoryId}";
            $session = Craft::$app->getSession();
            $sessionContestVoteTimestamp = $session->get($sessionKey);

            if ($sessionContestVoteTimestamp) {
                $lockoutFrequency = strtolower($contest->lockoutFrequency);
                $now = new \DateTime('now', new \DateTimeZone('America/New_York'));

                if ($lockoutFrequency === 'daily') {
                    // For daily frequency, check against the reset time
                    $resetTime = trim(getenv('CRAFT_DAILY_RESET_TIME') ?: '00:00', "'\"");
                    Craft::info("Using reset time from env: '{$resetTime}'", 'craft-cms-contests');

                    $resetTimeToday = new \DateTime($now->format('Y-m-d') . ' ' . $resetTime, new \DateTimeZone('America/New_York'));

                    // If current time is before reset time, use yesterday's reset time as the start of the period
                    $startOfPeriod = $now < $resetTimeToday
                        ? (clone $resetTimeToday)->modify('-1 day')
                        : $resetTimeToday;

                    Craft::info(sprintf(
                        "Current time: %s, Reset time today: %s, Start of period: %s",
                        $now->format('Y-m-d H:i:s T'),
                        $resetTimeToday->format('Y-m-d H:i:s T'),
                        $startOfPeriod->format('Y-m-d H:i:s T')
                    ), 'craft-cms-contests');

                    // Check if the last vote was after the start of the current period
                    $lastVoteTime = new \DateTime($sessionContestVoteTimestamp, new \DateTimeZone('America/New_York'));

                    Craft::info(sprintf(
                        "Last vote time: %s, Start of period: %s, Comparison: %s",
                        $lastVoteTime->format('Y-m-d H:i:s T'),
                        $startOfPeriod->format('Y-m-d H:i:s T'),
                        $lastVoteTime >= $startOfPeriod ? 'VOTED' : 'CAN VOTE'
                    ), 'craft-cms-contests');

                    if ($lastVoteTime >= $startOfPeriod) {
                        $category = \craft\elements\Category::find()
                            ->id($vote->categoryId)
                            ->one();

                        return [
                            "success" => false,
                            "message" => "You have already voted today. You can vote again after " . $resetTimeToday->format('g:i A') . ".",
                        ];
                    }
                } else {
                    // For other frequencies, use the existing logic
                    $epochNow = $now->getTimestamp();
                    $epochCreatedPlusTimeout = strtotime(
                        "+{$contest->lockoutLength} {$contest->lockoutFrequency}",
                        strtotime($sessionContestVoteTimestamp)
                    );

                    if ($epochNow < $epochCreatedPlusTimeout) {
                        $category = \craft\elements\Category::find()
                            ->id($vote->categoryId)
                            ->one();

                        return [
                            "success" => false,
                            "message" => "You can only vote once every {$contest->lockoutLength} {$contest->lockoutFrequency} for category '{$category->title}'. Please try again soon.",
                        ];
                    }
                }
            }
        }

        $voteRecord = new CraftcmsContestVoteRecord();

        // Run validations to make sure a record doesn't exist in the database within the lockout timeframe
        $vote->email = strtolower($vote->email);
        $emailValidation = $this->validateWithinTimeframe(
            "email",
            $vote->email,
            $vote->categoryId,
            $contest,
        );
        $ipValidation = $this->validateWithinTimeframe(
            "ip",
            $vote->ip,
            $vote->categoryId,
            $contest,
        );

        // Return an error message if validations fail
        if ($emailValidation) {
            // TODD: add $ipValidation as check, disabled for testing
            $lockoutLength = $contest->lockoutLength;
            $lockoutFrequency = $contest->lockoutFrequency;
            $lockoutFrequency =
                $lockoutLength > 1
                    ? $lockoutFrequency . "s"
                    : $lockoutFrequency;

            $category = \craft\elements\Category::find()
                ->id($vote->categoryId)
                ->one();

            return [
                "success" => false,
                "message" => "You can only vote once every {$lockoutLength} {$lockoutFrequency} for category '{$category->title}'. Please try again soon.",
            ];
        }

        // If validation passes, save the vote, return a success message
        if ($vote->save()) {

            $extraData = json_decode($vote->extraData);
            if(getenv('ACTON_VOTING_ENABLED') && $extraData->offers) {
                $this->sendVoterToActon($vote);
            }

            // Save the session timestamp for session protection
            if ($contest->sessionProtect) {
                $sessionKey = "craft-cms-contests:voteSessionProtectionTimestamp:{$vote->categoryId}";
                $timestamp = date("Y-m-d H:i:s");
                Craft::info("Setting session protection timestamp for category {$vote->categoryId}: {$timestamp}", 'craft-cms-contests');
                Craft::$app->getSession()->set($sessionKey, $timestamp);
            }

            return [
                "success" => true,
                "message" => "You vote has successfully been saved.",
            ];
        }

        // If vote fails to save, return an error message
        return [
            "success" => false,
            "message" =>
                "There was an error saving your vote. Please try again soon.",
        ];
    }

    // public function getEntryVoteCount($entryId, $contestId) {
    //     $queryResult = craft()->db->createCommand()
    //         ->select('count(entryId) as entryCount')
    //         ->from('contestify_votes')
    //         ->where(
    //             array(
    //                 "contestId" => $contestId,
    //                 "entryId" => $entryId
    //             )
    //         )
    //         ->group("entryId")
    //         ->queryAll();

    //     if (count($queryResult) == 0)
    //     {
    //         return 0;
    //     }
    //     else
    //     {
    //         return $queryResult[0]["entryCount"];
    //     }
    // }

    public function getAllVoteCountsByContestId($contestId): array
    {
        $queryResult = CraftcmsContestVoteRecord::find()
            ->select("count(entryId) as entryCount, entryId")
            ->where([
                "contestId" => $contestId,
            ])
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
        foreach ($queryResult as $result) {
            $map[$result["entryId"]] = (int) $result["entryCount"];
        }

        return $map;
    }

    public function sendVoterToActon($vote) {
        $extraData = json_decode($vote->extraData);
        $uri = 'https://api.actonsoftware.com/api/1/list/l-000d/record?email=' . $vote->email;
        $postfields = json_encode(["EMAIL"=>$vote->email,"FIRSTNAME"=>$extraData->fn,"LASTNAME"=>$extraData->ln]);

        $actonToken = $this->getActonToken();

        $curl = curl_init();

        curl_setopt_array($curl, [
          CURLOPT_URL => "https://api.actonsoftware.com/api/1/list/l-000d/record?email=".$vote->email,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "PUT",
          CURLOPT_POSTFIELDS => $postfields,
          CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . $actonToken,
            "accept: application/json",
            "content-type: application/json"
          ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        // if ($err) {
        //   echo "cURL Error #:" . $err;
        // } else {
        //   echo $response;
        // }
    }

    public function getActonToken() {

        $cache = Craft::$app->getCache();
        $cacheExpirationSeconds = 3550; // 0 seconds = never expires, 1 second = small amount of time to expire (i.e. "disable" caching)
        $cacheKey = "actonToken";
        $cache->delete($cacheKey);

        return $cache->getOrSet(
            $cacheKey,
            function() {
                $curl = curl_init();
                $url = 'https://api.actonsoftware.com/token';
                $postfields = 'username=' . env('ACTON_USERNAME');
                $postfields .= '&password=' . env('ACTON_PASSWORD');
                $postfields .= '&client_id=' . env('ACTON_API_CLIENT_ID');
                $postfields .= '&client_secret=' . env('ACTON_API_CLIENT_SECRET');
                $postfields .= '&grant_type=password';
                curl_setopt_array($curl, [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => $postfields,
                    CURLOPT_HTTPHEADER => [
                        "accept: application/json",
                        "content-type: application/x-www-form-urlencoded"
                    ]
                ]);
                $response = curl_exec($curl);
                $err = curl_error($curl);

                curl_close($curl);
                if ($err) {
                    throw new \Exception($error_msg);
                }

                $response = json_decode($response);

                return $response->access_token;

            },
            $cacheExpirationSeconds);
    }

    // Checks if a field value exists in the database within the lockout timeframe
    private function validateWithinTimeframe(
        $field,
        $value,
        $categoryId,
        $contest,
    ): ActiveRecord|array|null {
        Craft::info("Validating {$field} for category {$categoryId} with value: {$value}", 'craft-cms-contests');

        $dateRangeCriteria = $this->dateRangeCriteria($contest);
        Craft::info("Date range criteria: " . json_encode($dateRangeCriteria), 'craft-cms-contests');

        $query = CraftcmsContestVoteRecord::find()
            ->andWhere(["=", $field, $value])
            ->andWhere(["=", "categoryId", $categoryId])
            ->andWhere($dateRangeCriteria);

        $sql = $query->createCommand()->rawSql;
        Craft::info("SQL Query: " . $sql, 'craft-cms-contests');

        $rows = $query->one();

        if ($rows) {
            Craft::info("Found existing vote: " . json_encode([
                'id' => $rows->id,
                'dateCreated' => $rows->dateCreated,
                'email' => $rows->email,
                'ip' => $rows->ip
            ]), 'craft-cms-contests');
        } else {
            Craft::info("No existing vote found for {$field} = {$value} in the specified time period", 'craft-cms-contests');
        }

        return $rows;
    }

    // Sets up query criteria for records that fall within the specified time frame
    private function dateRangeCriteria($contest): array
    {
        $lockoutLength = $contest->lockoutLength;
        $lockoutFrequency = strtolower($contest->lockoutFrequency);
        $localTz = new \DateTimeZone('America/New_York');
        $utcTz = new \DateTimeZone('UTC');

        // Handle daily frequency specially - resets at configured time
        if ($lockoutFrequency === 'daily') {
            // Get the configured reset time (defaults to '00:00')
            $resetTime = trim(getenv('CRAFT_DAILY_RESET_TIME') ?: '00:00', "'\"");
            
            // Get current datetime in the local timezone
            $now = new \DateTime('now', $localTz);
            
            // Create reset time for today in local timezone
            $resetTimeToday = new \DateTime($now->format('Y-m-d') . ' ' . $resetTime, $localTz);
            
            // If current time is before reset time, use yesterday's reset time as the start of the period
            if ($now < $resetTimeToday) {
                $startOfPeriod = (clone $resetTimeToday)->modify('-1 day');
                Craft::debug('Using yesterday\'s reset time as start of period', 'craft-cms-contests');
            } else {
                $startOfPeriod = $resetTimeToday;
                Craft::debug('Using today\'s reset time as start of period', 'craft-cms-contests');
            }
            
            // Convert to UTC for database comparison
            $startOfPeriodUtc = clone $startOfPeriod;
            $startOfPeriodUtc->setTimezone($utcTz);
            $nowUtc = clone $now;
            $nowUtc->setTimezone($utcTz);
            
            if (Craft::$app->getConfig()->getGeneral()->devMode) {
                Craft::debug(sprintf(
                    'Daily voting window: %s to %s (UTC)',
                    $startOfPeriodUtc->format('Y-m-d H:i:s'),
                    $nowUtc->format('Y-m-d H:i:s')
                ), 'craft-cms-contests');
            }
            
            return [
                'and',
                ['>=', 'dateCreated', $startOfPeriodUtc->format('Y-m-d H:i:s')],
                ['<=', 'dateCreated', $nowUtc->format('Y-m-d H:i:s')]
            ];
        }
        
        // Handle legacy 'day' frequency (for backwards compatibility)
        if ($lockoutFrequency === 'day') {
            $startOfDay = new \DateTime('today', $localTz);
            $now = new \DateTime('now', $localTz);
            
            // Convert to UTC for database comparison
            $startOfDay->setTimezone($utcTz);
            $now->setTimezone($utcTz);
            
            Craft::info(sprintf(
                'Legacy day frequency - Querying for votes between %s and %s (UTC)',
                $startOfDay->format('Y-m-d H:i:s'),
                $now->format('Y-m-d H:i:s')
            ), 'craft-cms-contests');
            
            return [
                'and',
                ['>=', 'dateCreated', $startOfDay->format('Y-m-d H:i:s')],
                ['<=', 'dateCreated', $now->format('Y-m-d H:i:s')]
            ];
        }

        // For all other frequencies, use the existing relative time window logic
        $lockoutFrequency = $lockoutLength > 1 ? $contest->lockoutFrequency . "s" : $contest->lockoutFrequency;
        
        $now = new \DateTime('now', $localTz);
        $startDate = clone $now;
        
        // Calculate the start date based on frequency
        $startDate->modify("-$lockoutLength $lockoutFrequency");
        
        // Convert to UTC for database comparison
        $startDate->setTimezone($utcTz);
        $now->setTimezone($utcTz);
        
        Craft::info(sprintf(
            'Frequency: %s - Querying for votes between %s and %s (UTC)',
            $lockoutFrequency,
            $startDate->format('Y-m-d H:i:s'),
            $now->format('Y-m-d H:i:s')
        ), 'craft-cms-contests');
        
        return [
            'and',
            ['>=', 'dateCreated', $startDate->format('Y-m-d H:i:s')],
            ['<=', 'dateCreated', $now->format('Y-m-d H:i:s')]
        ];
    }
}
