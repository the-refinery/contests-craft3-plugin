<?php
/**
 * CraftCMS Contests plugin for Craft CMS 4.x
 *
 * This is a plugin that allows you to run contests with voting in your CraftCMS site
 *
 * @link      https://the-refinery.io
 * @copyright Copyright (c) 2021 The Refinery
 */

namespace therefinery\craftcmscontests\utilities;

use therefinery\craftcmscontests\CraftcmsContests;
use therefinery\craftcmscontests\assetbundles\craftcmscontestsutilityutility\CraftcmsContestsUtilityUtilityAsset;

use Craft;
use craft\base\Utility;

/**
 * CraftCMS Contests Utility
 *
 * @author    The Refinery
 * @package   CraftcmsContests
 * @since     1.0.0
 */
class CraftcmsContestsUtility extends Utility
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t("craft-cms-contests", "CraftcmsContestsUtility");
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return "craftcmscontests-craftcms-contests-utility";
    }

    /**
     * @inheritdoc
     */
    public static function iconPath(): string
    {
        return Craft::getAlias(
            "@therefinery/craftcmscontests/assetbundles/craftcmscontestsutilityutility/dist/img/CraftcmsContestsUtility-icon.svg",
        );
    }

    /**
     * @inheritdoc
     */
    public static function badgeCount(): int
    {
        return 0;
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        Craft::$app
            ->getView()
            ->registerAssetBundle(CraftcmsContestsUtilityUtilityAsset::class);

        $someVar = "Have a nice day!";
        return Craft::$app
            ->getView()
            ->renderTemplate(
                "craft-cms-contests/_components/utilities/CraftcmsContestsUtility_content",
                [
                    "someVar" => $someVar,
                ],
            );
    }
}
