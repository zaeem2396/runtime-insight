<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Contracts;

use ClarityPHP\RuntimeInsight\DTO\RootCauseResult;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;

/**
 * Analyzes runtime context to determine the most likely root cause.
 * Runs after Signal Collectors and before AI Explanation Engine.
 */
interface RootCauseAnalyzerInterface
{
    /**
     * Analyze context and return root cause result (primary cause, fix suggestions, prevention).
     */
    public function analyze(RuntimeContext $context): RootCauseResult;
}
