<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Engine;

use ClarityPHP\RuntimeInsight\Contracts\PatternAnalyzerInterface;
use ClarityPHP\RuntimeInsight\DTO\PatternMatchResult;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;

use function count;
use function is_array;
use function str_contains;
use function strtolower;

/**
 * Laravel-specific pattern detection: N+1 hints, validation-related errors.
 */
final class LaravelPatternAnalyzer implements PatternAnalyzerInterface
{
    private const N1_QUERY_THRESHOLD = 10;

    public function analyze(RuntimeContext $context): PatternMatchResult
    {
        $n1 = $this->detectN1Hint($context);
        if (! $n1->isEmpty()) {
            return $n1;
        }

        $validation = $this->detectValidationHint($context);
        if (! $validation->isEmpty()) {
            return $validation;
        }

        return new PatternMatchResult('');
    }

    private function detectN1Hint(RuntimeContext $context): PatternMatchResult
    {
        $count = 0;
        if ($context->databaseContext !== null && ! $context->databaseContext->isEmpty()) {
            $count = count($context->databaseContext->recentQueries);
        } elseif ($context->collectorsContext !== null && ! $context->collectorsContext->isEmpty()) {
            $signals = $context->collectorsContext->signals;
            $queryPayload = $signals['query'] ?? null;
            if (is_array($queryPayload) && isset($queryPayload['count']) && is_numeric($queryPayload['count'])) {
                $count = (int) $queryPayload['count'];
            }
        }

        if ($count < self::N1_QUERY_THRESHOLD) {
            return new PatternMatchResult('');
        }

        $frame = $context->stackTrace->getTopFrame();
        $location = $frame !== null && $frame->file !== null && $frame->line !== null
            ? $frame->file . ':' . $frame->line
            : $context->exception->file . ':' . $context->exception->line;

        return new PatternMatchResult(
            patternName: 'n_plus_one',
            summary: 'Many queries (' . $count . ') before failure; possible N+1 or missing eager loading.',
            location: $location,
            suggestions: [
                'Consider eager loading: Model::with("relation")->get() or $collection->load("relation").',
                'Reduce queries in loops or use a single query with proper joins.',
            ],
        );
    }

    private function detectValidationHint(RuntimeContext $context): PatternMatchResult
    {
        $message = $context->exception->message;
        $lower = strtolower($message);
        if (
            ! str_contains($lower, 'validation')
            && ! str_contains($lower, 'required')
            && ! str_contains($lower, 'invalid')
        ) {
            return new PatternMatchResult('');
        }

        $location = $context->exception->file . ':' . $context->exception->line;

        return new PatternMatchResult(
            patternName: 'validation',
            summary: 'Exception suggests a validation or input issue.',
            location: $location,
            suggestions: [
                'Check request validation rules (e.g. Form Request or validate()).',
                'Ensure required fields are present and types match.',
            ],
        );
    }
}
