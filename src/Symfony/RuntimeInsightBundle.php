<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Symfony;

use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

use function dirname;

/**
 * Symfony Bundle for Runtime Insight.
 */
class RuntimeInsightBundle extends AbstractBundle
{
    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}
