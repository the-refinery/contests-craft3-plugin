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

use Craft;
use craft\web\Controller;

/**
 * @author    The Refinery
 * @package   CraftcmsContests
 * @since     1.0.0
 */
class DefaultController extends Controller
{
    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = ["index", "do-something"];

    // Public Methods
    // =========================================================================

    /**
     * @return mixed
     */
    public function actionIndex(): mixed
    {
        $result = "Welcome to the DefaultController actionIndex() method";

        return $result;
    }

    /**
     * @return mixed
     */
    public function actionDoSomething(): mixed
    {
        $result = "Welcome to the DefaultController actionDoSomething() method";

        return $result;
    }
}
