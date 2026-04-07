<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Engine;

use ClarityPHP\RuntimeInsight\Contracts\RootCauseAnalyzerInterface;
use ClarityPHP\RuntimeInsight\DTO\RootCauseResult;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use ClarityPHP\RuntimeInsight\Engine\RootCause\ContextSummaryBuilder;
use ClarityPHP\RuntimeInsight\Engine\RootCause\ContributingNarrator;
use ClarityPHP\RuntimeInsight\Engine\RootCause\PrimaryCauseInferencer;
use ClarityPHP\RuntimeInsight\Engine\RootCause\RemediationBuilder;
use ClarityPHP\RuntimeInsight\Engine\RootCause\StackTraceAnalyzer;

/**
 * Determines the most likely root cause from runtime context.
 * Analyzes stack trace (vendor vs app), request/DB context, validation/config hints, and exception type.
 */
final class RootCauseAnalyzer implements RootCauseAnalyzerInterface
{
    public function __construct(
        private readonly PrimaryCauseInferencer $primaryCauseInferencer = new PrimaryCauseInferencer(),
        private readonly StackTraceAnalyzer $stackTraceAnalyzer = new StackTraceAnalyzer(),
        private readonly ContributingNarrator $contributingNarrator = new ContributingNarrator(),
        private readonly ContextSummaryBuilder $contextSummaryBuilder = new ContextSummaryBuilder(),
        private readonly RemediationBuilder $remediationBuilder = new RemediationBuilder(),
    ) {}

    public function analyze(RuntimeContext $context): RootCauseResult
    {
        $e = $context->exception;
        $message = $e->message;
        $class = $e->class;

        $inference = $this->primaryCauseInferencer->infer($class, $message);
        $stackSummary = $this->stackTraceAnalyzer->summarize($context->stackTrace);
        $contributing = $this->contributingNarrator->narrate($context, $stackSummary);
        $contextSummary = $this->contextSummaryBuilder->build($context);
        $remediation = $this->remediationBuilder->build($inference['category'], $context);

        $diagnostics = [
            'remediation_category' => $inference['category'],
            'vendor_frames' => $stackSummary['vendor_frames'],
            'app_frames' => $stackSummary['app_frames'],
            'first_app_frame' => $stackSummary['first_app_location'],
        ];

        return new RootCauseResult(
            primaryCause: $inference['primary'],
            contributing: $contributing,
            contextSummary: $contextSummary,
            fixSuggestions: $remediation['fixes'],
            preventionAdvice: $remediation['prevention'],
            diagnostics: $diagnostics,
        );
    }
}
