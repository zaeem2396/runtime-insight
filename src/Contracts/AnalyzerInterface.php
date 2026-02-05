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
     * Analyze a log entry (message, file, line) and generate an explanation.
     * Used when explaining from --log so "Where" shows the actual error location.
     */
    public function analyzeFromLog(string $message, string $file, int $line): Explanation;
}
