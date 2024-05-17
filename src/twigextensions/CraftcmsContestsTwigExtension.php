<?php
/**
 * CraftCMS Contests plugin for Craft CMS 4.x
 *
 * This is a plugin that allows you to run contests with voting in your CraftCMS site
 *
 * @link      https://the-refinery.io
 * @copyright Copyright (c) 2021 The Refinery
 */

namespace therefinery\craftcmscontests\twigextensions;

use therefinery\craftcmscontests\CraftcmsContests;

use Craft;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use verbb\vizy\gql\types\ArrayType;

/**
 * @author    The Refinery
 * @package   CraftcmsContests
 * @since     1.0.0
 */
class CraftcmsContestsTwigExtension extends AbstractExtension
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return "CraftcmsContests";
    }

    /**
     * @inheritdoc
     */
    public function getFilters(): array
    {
        return [new TwigFilter("someFilter", [$this, "someInternalFunction"])];
    }

    /**
     * @inheritdoc
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction("someFunction", [$this, "someInternalFunction"]),
        ];
    }

    /**
     * @param null $text
     *
     * @return string
     */
    public function someInternalFunction($text = null): string
    {
        $result = $text . " in the way";

        return $result;
    }
}
