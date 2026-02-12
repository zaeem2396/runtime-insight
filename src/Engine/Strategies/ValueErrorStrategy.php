<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Engine\Strategies;

use ClarityPHP\RuntimeInsight\Contracts\ExplanationStrategyInterface;
use ClarityPHP\RuntimeInsight\DTO\Explanation;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;

use function str_contains;

/**
 * Strategy for explaining ValueError (invalid value for operation).
 *
 * Handles ValueError for invalid enum, invalid regex, empty array to first(), etc.
 */
final class ValueErrorStrategy implements ExplanationStrategyInterface
{
    public function supports(RuntimeContext $context): bool
    {
        return str_contains($context->exception->class, 'ValueError');
    }

    public function explain(RuntimeContext $context): Explanation
    {
        $message = $context->exception->message;

        $cause = 'A value passed to a function or used in an operation was invalid for that operation. ' .
            'ValueError is thrown when the type is correct but the value is not allowed (e.g. empty array ' .
            'for first(), invalid enum case, or invalid regex). The message usually states what was expected.';

        $suggestions = [
            'Read the error message to see which value was rejected and why',
            'Validate input before calling the function (e.g. check array is not empty before first())',
            'For enums: ensure the value is a valid backed enum case',
            'For preg_*: ensure the pattern is valid and the subject is a string',
            'Add checks or defaults so invalid values are handled before the operation',
        ];

        return new Explanation(
            message: $message,
            cause: $cause,
            suggestions: $suggestions,
            confidence: 0.85,
            errorType: 'ValueError',
            location: "{$context->exception->file}:{$context->exception->line}",
        );
    }

    public function priority(): int
    {
        return 82;
    }
}
