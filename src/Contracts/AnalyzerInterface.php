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

    /**
     * Analyze a log entry (message, file, line, optional exception class) and generate an explanation.
     * Used when explaining from --log; exceptionClass allows rule-based strategies to match (e.g. TypeError).
     */
    public function analyzeFromLog(string $message, string $file, int $line, string $exceptionClass = 'Exception'): Explanation;
}
