<?php
/**
 * CraftCMS Contests plugin for Craft CMS 4.x
 *
 * This is a plugin that allows you to run contests with voting in your CraftCMS site
 *
 * @link      https://the-refinery.io
 * @copyright Copyright (c) 2021 The Refinery
 */

namespace therefinery\craftcmscontests\controllers;

use therefinery\craftcmscontests\CraftcmsContests;
use therefinery\craftcmscontests\records\CraftcmsContestVoteRecord;
use therefinery\craftcmscontests\records\CraftcmsContestRecord;
use therefinery\craftcmscontests\models\CraftcmsContestModel;

use Craft;
use craft\web\Controller;
use yii\web\Response;

/**
 * @author    The Refinery
 * @package   CraftcmsContests
 * @since     1.0.0
 */
class ContestsController extends Controller
{
    public function actionIndex(): Response
    {
        $variables = [];

        $contests = \therefinery\craftcmscontests\CraftcmsContests::getInstance()->contestService->getAllContests();

        $variables["contestItems"] = $contests;
        $variables["settings"] = [];

        return $this->renderTemplate(
            "craft-cms-contests/contests/index",
            $variables,
        );
    }

    public function actionEdit(int $contestId = null): void
    {
        $variables = [];

        // Get the contest passed in by id, or create a new one (new vs. edit)
        if (isset($contestId)) {
            $variables[
                "contest"
            ] = \therefinery\craftcmscontests\CraftcmsContests::getInstance()->contestService->getContestById(
                $contestId,
            );
        } else {
            $variables["contest"] = new CraftcmsContestModel();
        }

        $variables["categories"] = [];

        // Grab the categories associated with the contest so we can display them as we need.
        if (!empty($variables["contest"]["categories"])) {
            foreach (
                json_decode($variables["contest"]["categories"])
                as $categoryId
            ) {
                $asset = Craft::$app
                    ->getElements()
                    ->getElementById($categoryId);
                $variables["categories"][] = $asset;
            }
        }

        $this->renderTemplate("craft-cms-contests/contests/_edit", $variables);
    }

    public function actionSave(): Response
    {
        $this->requirePostRequest();
        $contest = new CraftcmsContestRecord();
        $request = Craft::$app->getRequest();

        $contest->id = $request->getBodyParam("contestId");
        $contest->name = $request->getBodyParam("name");
        $contest->handle = $request->getBodyParam("handle");
        $contest->categories = $request->getBodyParam("categories");
        $contest->lockoutLength = $request->getBodyParam("lockoutLength");
        $contest->lockoutFrequency = $request->getBodyParam("lockoutFrequency");
        $contest->enabled =
            $request->getBodyParam("enabled") == "1" ? true : false;
        $contest->sessionProtect =
            $request->getBodyParam("sessionProtect") == "1" ? true : false;
        $contest->recaptchaSecret = $request->getBodyParam("recaptchaSecret");

        if (
            \therefinery\craftcmscontests\CraftcmsContests::getInstance()->contestService->saveContest(
                $contest,
            )
        ) {
            \Craft::$app->session->setNotice(
                Craft::t("craft-cms-contests", "Contest saved."),
            );
        } else {
            \Craft::$app->session->setNotice(
                Craft::t("craft-cms-contests", "Could not save contest."),
            );
        }

        return $this->redirect("/admin-frogtape/craft-cms-contests/contests/");
    }

    public function actionDelete($id = null): Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $contestId = $request->getRequiredBodyParam("id");

        if (isset($contestId) && !empty($contestId)) {
            \therefinery\craftcmscontests\CraftcmsContests::getInstance()->contestService->deleteContestAndVotesById(
                $contestId,
            );
        } else {
            throw new \Exception("Contest ID was not set in the request.");
        }

        return $this->asJson(["success" => true]);
    }
}
