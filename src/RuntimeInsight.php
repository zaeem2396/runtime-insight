<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight;

use ClarityPHP\RuntimeInsight\Collectors\CollectorRegistry;
use ClarityPHP\RuntimeInsight\Contracts\AnalyzerInterface;
use ClarityPHP\RuntimeInsight\Contracts\ContextBuilderInterface;
use ClarityPHP\RuntimeInsight\Contracts\ExplanationEngineInterface;
use ClarityPHP\RuntimeInsight\Contracts\PatternAnalyzerInterface;
use ClarityPHP\RuntimeInsight\Contracts\RootCauseAnalyzerInterface;
use ClarityPHP\RuntimeInsight\DTO\Explanation;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use Throwable;

/**
 * Main entry point for Runtime Insight.
 *
 * Provides a unified API for analyzing runtime exceptions
 * and generating human-readable explanations.
 *
 * Pipeline order: context build → signal collectors → explain → root cause → pattern → result.
 */
final class RuntimeInsight implements AnalyzerInterface
{
    public function __construct(
        private readonly ContextBuilderInterface $contextBuilder,
        private readonly ExplanationEngineInterface $explanationEngine,
        private readonly Config $config,
        private readonly ?CollectorRegistry $collectorRegistry = null,
        private readonly ?RootCauseAnalyzerInterface $rootCauseAnalyzer = null,
        private readonly ?PatternAnalyzerInterface $patternAnalyzer = null,
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
        $context = $this->enrichContextWithCollectors($context);
        $explanation = $this->explanationEngine->explain($context);

        $explanation = $this->attachRootCauseToExplanation($context, $explanation);

        return $this->attachPatternToExplanation($context, $explanation);
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
        $context = $this->enrichContextWithCollectors($context);
        $explanation = $this->explanationEngine->explain($context);

        $explanation = $this->attachRootCauseToExplanation($context, $explanation);

        return $this->attachPatternToExplanation($context, $explanation);
    }

    /**
     * Analyze a pre-built runtime context.
     */
    public function analyzeContext(RuntimeContext $context): Explanation
    {
        if (! $this->config->isEnabled()) {
            return Explanation::empty();
        }

        $context = $this->enrichContextWithCollectors($context);
        $explanation = $this->explanationEngine->explain($context);

        $explanation = $this->attachRootCauseToExplanation($context, $explanation);

        return $this->attachPatternToExplanation($context, $explanation);
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

    private function enrichContextWithCollectors(RuntimeContext $context): RuntimeContext
    {
        if ($this->collectorRegistry === null) {
            return $context;
        }

        return $this->collectorRegistry->enrich($context);
    }

    private function attachRootCauseToExplanation(RuntimeContext $context, Explanation $explanation): Explanation
    {
        if ($this->rootCauseAnalyzer === null) {
            return $explanation;
        }

        $rootCause = $this->rootCauseAnalyzer->analyze($context);
        if ($rootCause->isEmpty()) {
            return $explanation;
        }

        return $explanation->withMetadata(['root_cause' => $rootCause->toArray()]);
    }

    private function attachPatternToExplanation(RuntimeContext $context, Explanation $explanation): Explanation
    {
        if ($this->patternAnalyzer === null) {
            return $explanation;
        }

        $pattern = $this->patternAnalyzer->analyze($context);
        if ($pattern->isEmpty()) {
            return $explanation;
        }

        return $explanation->withMetadata(['pattern' => $pattern->toArray()]);
    }
}
