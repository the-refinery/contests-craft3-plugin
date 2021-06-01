<?php
/**
 * CraftCMS Contests plugin for Craft CMS 3.x
 *
 * This is a plugin that allows you to run contests with voting in your CraftCMS site
 *
 * @link      https://the-refinery.io
 * @copyright Copyright (c) 2021 The Refinery
 */

namespace therefinery\craftcmscontests\widgets;

use therefinery\craftcmscontests\CraftcmsContests;
use therefinery\craftcmscontests\assetbundles\craftcmscontestswidgetwidget\CraftcmsContestsWidgetWidgetAsset;

use Craft;
use craft\base\Widget;

/**
 * CraftCMS Contests Widget
 *
 * @author    The Refinery
 * @package   CraftcmsContests
 * @since     1.0.0
 */
class CraftcmsContestsWidget extends Widget
{

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $message = 'Hello, world.';

    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('craft-cms-contests', 'CraftcmsContestsWidget');
    }

    /**
     * @inheritdoc
     */
    public static function iconPath()
    {
        return Craft::getAlias("@therefinery/craftcmscontests/assetbundles/craftcmscontestswidgetwidget/dist/img/CraftcmsContestsWidget-icon.svg");
    }

    /**
     * @inheritdoc
     */
    public static function maxColspan()
    {
        return null;
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules = array_merge(
            $rules,
            [
                ['message', 'string'],
                ['message', 'default', 'value' => 'Hello, world.'],
            ]
        );
        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate(
            'craft-cms-contests/_components/widgets/CraftcmsContestsWidget_settings',
            [
                'widget' => $this
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function getBodyHtml()
    {
        Craft::$app->getView()->registerAssetBundle(CraftcmsContestsWidgetWidgetAsset::class);

        return Craft::$app->getView()->renderTemplate(
            'craft-cms-contests/_components/widgets/CraftcmsContestsWidget_body',
            [
                'message' => $this->message
            ]
        );
    }
}
