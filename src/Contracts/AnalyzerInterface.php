<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Contracts;

use ClarityPHP\RuntimeInsight\DTO\Explanation;
use Throwable;

/**
 * Main analyzer interface for Runtime Insight.
 */
interface AnalyzerInterface
{
    /**
     * Analyze a throwable and generate an explanation.
     */
    public function analyze(Throwable $throwable): Explanation;
}

