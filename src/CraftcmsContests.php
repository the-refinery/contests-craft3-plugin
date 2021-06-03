<?php
/**
 * CraftCMS Contests plugin for Craft CMS 3.x
 *
 * This is a plugin that allows you to run contests with voting in your CraftCMS site
 *
 * @link      https://the-refinery.io
 * @copyright Copyright (c) 2021 The Refinery
 */

namespace therefinery\craftcmscontests;

use therefinery\craftcmscontests\services\CraftcmsContestService as CraftcmsContestServiceService;
use therefinery\craftcmscontests\variables\CraftcmsContestsVariable;
use therefinery\craftcmscontests\twigextensions\CraftcmsContestsTwigExtension;
use therefinery\craftcmscontests\models\Settings;
use therefinery\craftcmscontests\utilities\CraftcmsContestsUtility as CraftcmsContestsUtilityUtility;
use therefinery\craftcmscontests\widgets\CraftcmsContestsWidget as CraftcmsContestsWidgetWidget;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\console\Application as ConsoleApplication;
use craft\web\UrlManager;
use craft\services\Utilities;
use craft\web\twig\variables\CraftVariable;
use craft\services\Dashboard;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterCpNavItemsEvent;
use craft\web\twig\variables\Cp;

use yii\base\Event;

/**
 * Class CraftcmsContests
 *
 * @author    The Refinery
 * @package   CraftcmsContests
 * @since     1.0.0
 *
 * @property  CraftcmsContestsServiceService $craftcmsContestsService
 */
class CraftcmsContests extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var CraftcmsContests
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $schemaVersion = '1.0.0';

    /**
     * @var bool
     */
    public $hasCpSettings = false;

    /**
     * @var bool
     */
    public $hasCpSection = true;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;
        $this->setComponents([
            'contestService' => \therefinery\craftcmscontests\services\CraftcmsContestService::class,
            'contestVoteService' => \therefinery\craftcmscontests\services\CraftcmsContestVoteService::class,
        ]);

        Craft::$app->view->registerTwigExtension(new CraftcmsContestsTwigExtension());

        if (Craft::$app instanceof ConsoleApplication) {
            $this->controllerNamespace = 'therefinery\craftcmscontests\console\controllers';
        }

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['craft-cms-contests/votes/saveVoteAsync'] = 'craft-cms-contests/votes/save-vote-async';
                $event->rules['craft-cms-contests/votes/saveVote'] = 'craft-cms-contests/votes/save-vote';
            }
        );

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['craft-cms-contests/contests'] = 'craft-cms-contests/contests/index';
                $event->rules['craft-cms-contests/contests/new'] = 'craft-cms-contests/contests/edit';
                $event->rules['craft-cms-contests/contests/delete'] = 'craft-cms-contests/contests/delete';
                $event->rules['craft-cms-contests/contests/<contestId:\d+>/edit'] = 'craft-cms-contests/contests/edit';
                $event->rules['craft-cms-contests/votes'] = 'craft-cms-contests/votes/index';
                $event->rules['craft-cms-contests/votes-by-contest/<contestId:\d+>'] = 'craft-cms-contests/votes/votes-by-contest';
            }
        );

        Event::on(
            Utilities::class,
            Utilities::EVENT_REGISTER_UTILITY_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = CraftcmsContestsUtilityUtility::class;
            }
        );

        Event::on(
            Dashboard::class,
            Dashboard::EVENT_REGISTER_WIDGET_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = CraftcmsContestsWidgetWidget::class;
            }
        );

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('craftcmsContests', CraftcmsContestsVariable::class);
            }
        );

        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                }
            }
        );

        Craft::info(
            Craft::t(
                'craft-cms-contests',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'craft-cms-contests/settings',
            [
                'settings' => $this->getSettings()
            ]
        );
    }

    public function getCpNavItem()
    {
        $item = parent::getCpNavItem();
        $item['subnav'] = [
            'contests' => ['label' => 'Contests', 'url' => 'craft-cms-contests/contests'],
            'votes' => ['label' => 'Votes', 'url' => 'craft-cms-contests/votes'],
        ];
        return $item;
    }
}
