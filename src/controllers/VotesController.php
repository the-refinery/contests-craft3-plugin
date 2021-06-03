<?php
/**
 * CraftCMS Contests plugin for Craft CMS 3.x
 *
 * This is a plugin that allows you to run contests with voting in your CraftCMS site
 *
 * @link      https://the-refinery.io
 * @copyright Copyright (c) 2021 The Refinery
 */

namespace therefinery\craftcmscontests\controllers;

use therefinery\craftcmscontests\CraftcmsContests;
use therefinery\craftcmscontests\records\CraftcmsContestVoteRecord;
use therefinery\craftcmscontests\assetbundles\craftcmscontests\CraftcmsContestsAsset;

use Craft;
use craft\web\Controller;

/**
 * @author    The Refinery
 * @package   CraftcmsContests
 * @since     1.0.0
 */
class VotesController extends Controller
{
    protected $allowAnonymous = ['save-vote-async', 'save-vote'];

    // Disable default CSRF protection on async calls
    public function beforeAction($action) {
        if ($action->id === 'save-vote-async') {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    public function actionIndex()
    {
        $contestItems = \therefinery\craftcmscontests\CraftcmsContests::getInstance()
            ->contestService
            ->getAllContests();

        $variables['contestItems'] = $contestItems;

        return $this->renderTemplate('craft-cms-contests/votes/index', $variables);
    }

    public function actionSaveVoteAsync() {
        $this->requirePostRequest();

        $data = json_decode(Craft::$app->request->getRawBody(), true);
        $ipAddress = Craft::$app->request->userIP;
        $userAgent = substr(Craft::$app->request->userAgent, 0, 511);

        // Check for key "csrfToken" in the incoming data and it's value.
        // Fail the request if the token is not supplied or is incorrect.
        // In the frontend (via twig), you can generate a hidden input field with
        // an appropriate value by using:
        // <input type="hidden" id="csrfToken" name="csrfToken" value="{{ craft.request.getCsrfToken() }}">
        // The server-side validation merely checks to ensure that the values are the same.
        if(array_key_exists("csrfToken", $data)) {
            $requestToken = $data["csrfToken"];
            $unmaskedRequestToken = Craft::$app->getSecurity()->unmaskToken($requestToken);

            if ($unmaskedRequestToken !== Craft::$app->getSecurity()->unmaskToken(Craft::$app->request->csrfToken)) {
                // If the token is supplied but is invalid, as in possibly being forged or is stale...
                Craft::warning("ERROR: incoming async vote payload has invalid CSRF token.", "craft-cms-contests");
                return $this->asJson(
                    array(
                        "status" => "error",
                        "errors" => array(
                            array(
                                "detail" => "Invalid CSRF token supplied. Please refresh and try again."
                            )
                        )
                    )
                )->setStatusCode(400);
            }
        } else {
            // Incoming payload does not have a CSRF token. Fail.
            Craft::warning("WARNING: incoming async vote payload does not have CSRF token.", "craft-cms-contests");
            return $this->asJson(
                array(
                    "status" => "error",
                    "errors" => array(
                        array(
                            "status" => "error", "errors" => array( array( "detail" => "CSRF token not supplied."))
                        )
                    )
                )
            )->setStatusCode(400);
        }

        $contest = \therefinery\craftcmscontests\CraftcmsContests::getInstance()
            ->contestService
            ->getContestById($data["contestId"]);

        if(!$contest) {
            Craft::warning("WARNING: incoming async vote payload does not have a valid contest.", "craft-cms-contests");
            return $this->asJson(
                array(
                    "status" => "error",
                    "errors" => array(
                        array(
                            "detail" => "Invalid contest suppplied."
                        )
                    )
                )
            )->setStatusCode(400);
        }

        // If recaptcha is set up for this contest
        if(!empty($contest->recaptchaSecret)){
            // recaptcha is set up and the response is in the payload
            if(array_key_exists("recaptchaResponse", $data)) {
                // Send the captcha request to google and get the response
                $response = \therefinery\craftcmscontests\CraftcmsContests::getInstance()
                    ->contestService
                    ->validateRecaptcha($contest, $ipAddress, $data["recaptchaResponse"]);

                // Return an error if the response coming back from Google is not successful.
                if(!$response->isSuccess()) {
                    Craft::warning("WARNING: Failure reCaptcha response coming back from Google on contest id={$contest->id}. Error codes: {$errorCodes}", "craft-cms-contests");
                    return $this->asJson(
                        array(
                            "status" => "error",
                            "errors" => array(
                                array(
                                    "detail" => "There were some reCaptcha issues. Please try again soon."
                                )
                            )
                        )
                    )->setStatusCode(400);
                }
            } else {// recaptcha is set up and response is not in the payload, this is an error
                Craft::warning("WARNING: incoming vote does not have a recaptchaResponse in data, but recaptcha is enabled on contest id={$contest->id}.", "craft-cms-contests");
                return $this->asJson(
                    array(
                        "status" => "error",
                        "errors" => array(
                            array(
                                "detail" => "There were some reCaptcha issues. Please try again soon."
                            )
                        )
                    )
                )->setStatusCode(400);
            }
        }

        // Process all the incoming votes
        $errors = [];
        foreach($data["data"] as $voteData) {
            $vote = new CraftcmsContestVoteRecord();
            $vote->contestId = $contest->id;
            $vote->categoryId = $voteData['categoryId'] ?? null;
            $vote->entryId = $voteData['entryId'] ?? null;
            $vote->email = $voteData['email'] ?? null;
            $vote->extraData = $voteData['extraData'] ?? null;
            $vote->ip = $ipAddress;
            $vote->userAgent = $userAgent;

            $saveVote = \therefinery\craftcmscontests\CraftcmsContests::getInstance()
                ->contestVoteService
                ->saveVote($vote, $contest);

            if(!$saveVote['success'])
            {
                array_push(
                    $errors,
                    array("detail" => $saveVote['message'])
                );
            }
        }

        // If there were any errors processing votes above
        if (!empty($errors))
        {
            return $this->asJson(
                array(
                    "status" => "error",
                    "errors" => $errors
                )
            )->setStatusCode(400);
        }

        // All good, votes were cast successfully.
        return $this->asJson(
            array(
                "status" => "success",
                "message" => "Votes successfully cast."
            )
        );

    }

    public function actionSaveVote() {
    }

    public function actionVotesByContest($contestId = null) {
        $this->view->registerAssetBundle(CraftcmsContestsAsset::class);

        // We will need the contest in the frontend.
        $contest = \therefinery\craftcmscontests\CraftcmsContests::getInstance()
            ->contestService
            ->getContestById($contestId);

        $variables["contest"] = $contest;

        // Get all the current vote counts and all possible entries for the current contest
        // We will combine the vote counts + entries below
        $voteCounts = \therefinery\craftcmscontests\CraftcmsContests::getInstance()
            ->contestVoteService
            ->getAllVoteCountsByContestId($contestId);

        $allEntries = \therefinery\craftcmscontests\CraftcmsContests::getInstance()
            ->contestService
            ->getAllEntriesByContestId($contestId);

        $voteCountsWithEntries = [];

        // $voteCountsWithEntries[0] = number of votes
        // $voteCountsWithEntries[1] = Craft entry
        foreach($allEntries as $entry) {
            array_push(
                $voteCountsWithEntries,
                array($voteCounts[$entry->id] ?? 0, $entry)
            );
        }

        // Sort the vote counts by the number of votes.
        // $voteCountsWithEntries[0] = number of votes
        // $voteCountsWithEntries[1] = Craft entry
        usort(
            $voteCountsWithEntries,
            function($a, $b){
                if ($a[0]==$b[0]) return 0;
                return($a[0]<$b[0]?1:-1);
            }
        );

        // Create Vote Chart Data which will be used by the Chart.js display.
        // See votesByContest.twig for details.
        $voteChartData = [];

        // The Chart labels are the titles of each Entry.
        $voteChartData["labels"] = array_map(
            function($voteCountEntry){
                return $voteCountEntry[1]->title;
            },
            $voteCountsWithEntries
        );

        // The Chart datapoints are the number of votes corresponding to the labels above
        $voteChartData["data"] = array_map(
            function($voteCountEntry){
                return $voteCountEntry[0];
            },
            $voteCountsWithEntries
        );

        $variables["voteChartData"] = $voteChartData;

        // Count up the total votes as a variable
        $variables["totalVotes"] = 0;
        foreach($voteCountsWithEntries as $voteCountEntry) {
            $variables["totalVotes"] += $voteCountEntry[0];
        }

        $variables["voteCountsWithEntries"] = $voteCountsWithEntries;

        return $this->renderTemplate('craft-cms-contests/votes/votesByContest', $variables);
    }
}
