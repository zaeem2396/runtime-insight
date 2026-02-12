<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Engine\Strategies;

use ClarityPHP\RuntimeInsight\Contracts\ExplanationStrategyInterface;
use ClarityPHP\RuntimeInsight\DTO\Explanation;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;

use function str_contains;

/**
 * Strategy for explaining division by zero errors.
 *
 * Handles DivisionByZeroError and legacy division-by-zero notices.
 */
final class DivisionByZeroErrorStrategy implements ExplanationStrategyInterface
{
    public function supports(RuntimeContext $context): bool
    {
        if (str_contains($context->exception->class, 'DivisionByZeroError')) {
            return true;
        }

        $message = $context->exception->message;

        return str_contains($message, 'Division by zero') ||
               str_contains($message, 'division by zero');
    }

    public function explain(RuntimeContext $context): Explanation
    {
        $cause = 'A division by zero was attempted. The divisor (denominator) is zero, ' .
            'which is not allowed in arithmetic. This often happens when a variable used as ' .
            'the divisor is zero or when user input is not validated.';

        $suggestions = [
            'Check that the divisor is not zero before dividing',
            'Use a conditional: if ($divisor !== 0) { $result = $a / $divisor; }',
            'Validate user input or configuration values that feed into the divisor',
            'Consider using a default or fallback when the divisor might be zero',
        ];

        return new Explanation(
            message: $context->exception->message,
            cause: $cause,
            suggestions: $suggestions,
            confidence: 0.90,
            errorType: 'DivisionByZeroError',
            location: "{$context->exception->file}:{$context->exception->line}",
        );
    }

    public function priority(): int
    {
        return 82;
    }
}
