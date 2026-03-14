<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Contracts;

use ClarityPHP\RuntimeInsight\DTO\PatternMatchResult;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;

/**
 * Analyzes runtime context for framework/error patterns (N+1, validation, etc.).
 * Runs after Root Cause Analyzer when applicable.
 */
interface PatternAnalyzerInterface
{
    /**
     * Analyze context and return the first matching pattern, or an empty result.
     */
    public function analyze(RuntimeContext $context): PatternMatchResult;
}
