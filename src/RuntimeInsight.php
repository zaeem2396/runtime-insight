<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight;

use ClarityPHP\RuntimeInsight\Contracts\AnalyzerInterface;
use ClarityPHP\RuntimeInsight\Contracts\ContextBuilderInterface;
use ClarityPHP\RuntimeInsight\Contracts\ExplanationEngineInterface;
use ClarityPHP\RuntimeInsight\DTO\Explanation;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use Throwable;

/**
 * Main entry point for Runtime Insight.
 *
 * Provides a unified API for analyzing runtime exceptions
 * and generating human-readable explanations.
 */
final class RuntimeInsight implements AnalyzerInterface
{
    public function __construct(
        private readonly ContextBuilderInterface $contextBuilder,
        private readonly ExplanationEngineInterface $explanationEngine,
        private readonly Config $config,
    ) {}

    /**
     * Analyze a throwable and generate an explanation.
     */
    public function analyze(Throwable $throwable): Explanation
    {
        if (! $this->config->isEnabled()) {
            return Explanation::empty();
        }

        $context = $this->contextBuilder->build($throwable);

        return $this->explanationEngine->explain($context);
    }

    /**
     * Analyze a log entry (message, file, line, optional exception class) and generate an explanation.
     */
    public function analyzeFromLog(string $message, string $file, int $line, string $exceptionClass = 'Exception'): Explanation
    {
        if (! $this->config->isEnabled()) {
            return Explanation::empty();
        }

        $context = $this->contextBuilder->buildFromLogEntry($message, $file, $line, $exceptionClass);

        return $this->explanationEngine->explain($context);
    }

    /**
     * Analyze a pre-built runtime context.
     */
    public function analyzeContext(RuntimeContext $context): Explanation
    {
        if (! $this->config->isEnabled()) {
            return Explanation::empty();
        }

        return $this->explanationEngine->explain($context);
    }

    /**
     * Check if Runtime Insight is enabled for the current environment.
     */
    public function isEnabled(): bool
    {
        return $this->config->isEnabled();
    }

    /**
     * Check if AI analysis is available.
     */
    public function isAIEnabled(): bool
    {
        return $this->config->isAIEnabled();
    }
}
