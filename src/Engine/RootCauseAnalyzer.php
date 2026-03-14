<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Engine;

use ClarityPHP\RuntimeInsight\Contracts\RootCauseAnalyzerInterface;
use ClarityPHP\RuntimeInsight\DTO\RootCauseResult;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;

use function str_contains;
use function strtolower;

/**
 * Determines the most likely root cause from runtime context.
 * Analyzes stack trace, request/DB context, and exception type.
 */
final class RootCauseAnalyzer implements RootCauseAnalyzerInterface
{
    public function analyze(RuntimeContext $context): RootCauseResult
    {
        $e = $context->exception;
        $message = $e->message;
        $class = $e->class;

        $primary = $this->inferPrimaryCause($class, $message, $context);
        $contributing = $this->inferContributing($context);
        $contextSummary = $this->buildContextSummary($context);
        $fixSuggestions = $this->buildFixSuggestions($class, $message, $context);
        $prevention = $this->buildPreventionAdvice($class, $context);

        return new RootCauseResult(
            primaryCause: $primary,
            contributing: $contributing,
            contextSummary: $contextSummary,
            fixSuggestions: $fixSuggestions,
            preventionAdvice: $prevention,
        );
    }

    private function inferPrimaryCause(string $class, string $message, RuntimeContext $context): string
    {
        if (str_contains(strtolower($class), 'typeerror')) {
            if (str_contains($message, 'null given') || str_contains($message, 'on null')) {
                return 'Null value passed where a non-null type was expected (type error).';
            }

            return 'Type mismatch: ' . $message;
        }

        if (str_contains(strtolower($message), 'on null') || str_contains($message, 'null pointer')) {
            return 'A null reference was accessed (method or property on null).';
        }

        if (str_contains($message, 'Undefined array key') || str_contains($message, 'Undefined index')) {
            return 'An array key was missing or undefined.';
        }

        if (str_contains($message, 'not found')) {
            return 'A required class, interface, or resource was not found.';
        }

        return 'Runtime failure: ' . $message;
    }

    private function inferContributing(RuntimeContext $context): string
    {
        $parts = [];

        if ($context->requestContext !== null) {
            $parts[] = $context->requestContext->method . ' ' . $context->requestContext->uri;
            if ($context->requestContext->route !== null && $context->requestContext->route !== '') {
                $parts[] = 'Route: ' . $context->requestContext->route;
            }
        }

        if ($context->databaseContext !== null && ! $context->databaseContext->isEmpty()) {
            $parts[] = 'Query log shows ' . count($context->databaseContext->recentQueries) . ' query(ies) before failure.';
        }

        return $parts === [] ? '' : implode('. ', $parts);
    }

    private function buildContextSummary(RuntimeContext $context): string
    {
        return $context->exception->file . ':' . $context->exception->line;
    }

    /**
     * @return array<string>
     */
    private function buildFixSuggestions(string $class, string $message, RuntimeContext $context): array
    {
        $suggestions = [];

        if (str_contains($message, 'on null') || str_contains($message, 'null given')) {
            $suggestions[] = 'Add a null check before accessing the value (e.g. if ($x !== null)).';
            $suggestions[] = 'Use the nullsafe operator (?->) or provide a default with ??';
        }

        if (str_contains($message, 'Undefined array key') || str_contains($message, 'Undefined index')) {
            $suggestions[] = 'Check that the key exists with isset() or array_key_exists() before access.';
            $suggestions[] = 'Use the null coalescing operator ?? to provide a default value.';
        }

        if (str_contains(strtolower($class), 'typeerror')) {
            $suggestions[] = 'Ensure the argument or property has the expected type at the call site.';
        }

        if ($context->requestContext !== null) {
            $suggestions[] = 'Validate request input and ensure required parameters are present.';
        }

        if ($suggestions === []) {
            $suggestions[] = 'Review the stack trace and error location for the exact failing operation.';
        }

        return $suggestions;
    }

    /**
     * @return array<string>
     */
    private function buildPreventionAdvice(string $class, RuntimeContext $context): array
    {
        $advice = [];

        if (str_contains($context->exception->message, 'on null') || str_contains($context->exception->message, 'null given')) {
            $advice[] = 'Add guards or validation so null is handled before the failing call.';
        }

        if ($context->requestContext !== null) {
            $advice[] = 'Consider middleware or validation to enforce preconditions for this route.';
        }

        if ($advice === []) {
            $advice[] = 'Add defensive checks and tests around the failing code path.';
        }

        return $advice;
    }
}
