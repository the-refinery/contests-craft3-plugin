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

use Craft;
use craft\base\Component;

/**
 * @author    The Refinery
 * @package   CraftcmsContests
 * @since     1.0.0
 */
class CraftcmsContestService extends Component
{
    public function getAllContests($indexBy = null) {
        return CraftcmsContestRecord::find()->all();
    }

    public function getContestByHandle($contestHandle)
    {
        return CraftcmsContestRecord::find()
            ->where(['handle' => $contestHandle])
            ->one();
    }

    public function getContestById($contestId)
    {
        if(isset($contestId) && !empty($contestId)) {
            $contest = CraftcmsContestRecord::find()
                ->where(['id' => $contestId])
                ->one();
            return $contest;
        } else {
            return null;
        }
    }

    public function saveContest(CraftcmsContestRecord $contest)
    {
        if($contest->id) {
            $contestRecord = CraftcmsContestRecord::find()
                ->where(['id' => $contest->id])
                ->one();

            if (!$contestRecord) {
                throw new Exception(Craft::t('No form exists with the ID “{id}”', array('id' => $contest->id)));
            }
        } else {
            $contestRecord = new CraftcmsContestRecord();
        }

        $contestRecord->name        = $contest->name;
        $contestRecord->handle      = $contest->handle;
        $contestRecord->categories  = $contest->categories;
        $contestRecord->dateStart   = $contest->dateStart;
        $contestRecord->dateEnd     = $contest->dateEnd;
        $contestRecord->lockoutLength       = $contest->lockoutLength;
        $contestRecord->lockoutFrequency    = $contest->lockoutFrequency;
        $contestRecord->enabled             = $contest->enabled;
        $contestRecord->sessionProtect      = $contest->sessionProtect;
        $contestRecord->recaptchaSecret     = $contest->recaptchaSecret;

        $contestRecord->validate();
        $contest->addErrors($contestRecord->getErrors());

        if (!$contest->hasErrors()) {
            $transaction = \Craft::$app->getDb()->getTransaction() ?? \Craft::$app->getDb()->beginTransaction();
            try {
                $contestRecord->save();

                if (!$contest->id) { $contest->id = $contestRecord->id; }

                if ($transaction !== null) { $transaction->commit(); }
            } catch (\Exception $e) {
                if ($transaction !== null) { $transaction->rollback(); }
                throw $e;
            }
            return true;
        } else {
            return false;
        }
    }

    public function deleteContestAndVotesById($contestId) {
        if(isset($contestId) && !empty($contestId)) {
            $transaction = \Craft::$app->getDb()->getTransaction() ?? \Craft::$app->getDb()->beginTransaction();
            try {
                // Delete all the votes associated wiht the contest first, because they have a foreign key
                // constraint
                CraftcmsContestVoteRecord::deleteAll(['contestId' => $contestId]);

                // Now delete the contest
                $contest = CraftcmsContestRecord::findOne($contestId);
                $contest->delete();

                if ($transaction !== null) { $transaction->commit(); }
            } catch (\Exception $e) {
                if ($transaction !== null) { $transaction->rollback(); }
                throw $e;
            }
            return true;
        } else {
            throw new \Exception("contestId must be supplied to this method");
        }

        return true;
    }

    public function getAllEntriesByContestId($contestId) {
        // Get the Contest Categories
        $category = $this->getContestById($contestId);
        $contestCategoryIds = json_decode($category->categories);

        if($contestCategoryIds === NULL) {
            Craft::warning("WARNING: Contest (ID={$contestId}) does not have any categories.", "contestify");
            return [];
        }

        // Get all elements which belong to the contest categories
        $categories = \craft\elements\Category::find()
            ->id($contestCategoryIds)
            ->all();

        $entries = \craft\elements\Entry::find()
            ->relatedTo($categories)
            ->all();

        return $entries;
    }

    public function validateRecaptcha($contest, $ipAddress, $recaptchaResponse) {
        $recaptcha = new \ReCaptcha\ReCaptcha($contest->recaptchaSecret);

        $resp = $recaptcha->verify($recaptchaResponse, $ipAddress);

        return $resp;
    }
}
