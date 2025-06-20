<?php
/**
 * CraftCMS Contests plugin for Craft CMS 4.x
 *
 * This is a plugin that allows you to run contests with voting in your CraftCMS site
 *
 * @link      https://the-refinery.io
 * @copyright Copyright (c) 2021 The Refinery
 */

return [
    // The time of day when daily voting resets (24-hour format, e.g., '00:00' for midnight)
    // This can be overridden in your .env file with CRAFT_DAILY_RESET_TIME
    'dailyResetTime' => getenv('CRAFT_DAILY_RESET_TIME') ?: '00:00',
];
