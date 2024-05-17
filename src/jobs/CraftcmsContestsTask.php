<?php
/**
 * CraftCMS Contests plugin for Craft CMS 4.x
 *
 * This is a plugin that allows you to run contests with voting in your CraftCMS site
 *
 * @link      https://the-refinery.io
 * @copyright Copyright (c) 2021 The Refinery
 */

namespace therefinery\craftcmscontests\jobs;

use therefinery\craftcmscontests\CraftcmsContests;

use Craft;
use craft\queue\BaseJob;

/**
 * @author    The Refinery
 * @package   CraftcmsContests
 * @since     1.0.0
 */
class CraftcmsContestsTask extends BaseJob
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public string $someAttribute = "Some Default";

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function execute($queue): void
    {
        // Do work here
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return Craft::t("craft-cms-contests", "CraftcmsContestsTask");
    }
}
