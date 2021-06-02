<?php
/**
 * CraftCMS Contests plugin for Craft CMS 3.x
 *
 * This is a plugin that allows you to run contests with voting in your CraftCMS site
 *
 * @link      https://the-refinery.io
 * @copyright Copyright (c) 2021 The Refinery
 */

namespace therefinery\craftcmscontests\variables;

use therefinery\craftcmscontests\CraftcmsContests;

use Craft;

/**
 * @author    The Refinery
 * @package   CraftcmsContests
 * @since     1.0.0
 */
class CraftcmsContestsVariable
{
    public function getAllVoteCountsByContestId($contestId)
    {
        // TODO
        // return craft()->contestify_votes->getAllVoteCountsByContestId($contestId);
    }

    public function getAllVoteCountByEntryIdAndContestId($entryId, $contestId)
    {
        // TODO
        // return craft()->contestify_votes->getEntryVoteCount($entryId, $contestId);
    }

    public function getContestByHandle($contestHandle){
        return \therefinery\craftcmscontests\CraftcmsContests::getInstance()
            ->contestService
            ->getContestByHandle($contestHandle);
    }
}
