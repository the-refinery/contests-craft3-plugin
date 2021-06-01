<?php
/**
 * CraftCMS Contests plugin for Craft CMS 3.x
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
class CraftcmsContestModel extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    // public $someAttribute = 'Some Default';
    public $id;
    public $uid;
    public $dateCreated;
    public $dateUpdated;
    public $name;
    public $handle;
    public $categories;
    public $dateStart;
    public $dateEnd;
    public $lockoutLength;
    public $lockoutFrequency;
    public $enabled;
    public $sessionProtect;
    public $recaptchaSecret;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        // return [
        //     ['someAttribute', 'string'],
        //     ['someAttribute', 'default', 'value' => 'Some Default'],
        // ];
    }
}
