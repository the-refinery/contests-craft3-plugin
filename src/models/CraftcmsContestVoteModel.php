<?php
/**
 * CraftCMS Contests plugin for Craft CMS 4.x
 *
 * This is a plugin that allows you to run contests with voting in your CraftCMS site
 *
 * @link      https://the-refinery.io
 * @copyright Copyright (c) 2021 The Refinery
 */

namespace therefinery\craftcmscontests\models;

use therefinery\craftcmscontests\CraftcmsContests;

use Craft;
use craft\base\Model;

/**
 * @author    The Refinery
 * @package   CraftcmsContests
 * @since     1.0.0
 */
class CraftcmsContestVoteModel extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $id;
    public $dateCreated;
    public $dateUpdated;
    public $uid;
    public $contestId;
    public $categoryId;
    public $entryId;
    public $email;
    public $ip;
    public $extraData;
    public $userAgent;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            ["someAttribute", "string"],
            ["someAttribute", "default", "value" => "Some Default"],
        ];
    }
}
