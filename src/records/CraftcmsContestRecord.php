<?php
/**
 * CraftCMS Contests plugin for Craft CMS 3.x
 *
 * This is a plugin that allows you to run contests with voting in your CraftCMS site
 *
 * @link      https://the-refinery.io
 * @copyright Copyright (c) 2021 The Refinery
 */

namespace therefinery\craftcmscontests\records;

use therefinery\craftcmscontests\CraftcmsContests;

use Craft;
use craft\db\ActiveRecord;

/**
 * @author    The Refinery
 * @package   CraftcmsContests
 * @since     1.0.0
 */
class CraftcmsContestRecord extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%craftcms_contests}}';
    }
}
