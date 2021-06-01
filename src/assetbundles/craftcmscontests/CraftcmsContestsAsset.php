<?php
/**
 * CraftCMS Contests plugin for Craft CMS 3.x
 *
 * This is a plugin that allows you to run contests with voting in your CraftCMS site
 *
 * @link      https://the-refinery.io
 * @copyright Copyright (c) 2021 The Refinery
 */

namespace therefinery\craftcmscontests\assetbundles\craftcmscontests;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    The Refinery
 * @package   CraftcmsContests
 * @since     1.0.0
 */
class CraftcmsContestsAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = "@therefinery/craftcmscontests/assetbundles/craftcmscontests/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/CraftcmsContests.js',
        ];

        $this->css = [
            'css/CraftcmsContests.css',
        ];

        parent::init();
    }
}
