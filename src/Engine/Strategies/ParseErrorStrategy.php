<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Engine\Strategies;

use ClarityPHP\RuntimeInsight\Contracts\ExplanationStrategyInterface;
use ClarityPHP\RuntimeInsight\DTO\Explanation;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;

use function str_contains;

/**
 * Strategy for explaining PHP parse/syntax errors.
 *
 * Handles ParseError thrown when PHP encounters invalid syntax (e.g. missing bracket, typo).
 */
final class ParseErrorStrategy implements ExplanationStrategyInterface
{
    public function supports(RuntimeContext $context): bool
    {
        return str_contains($context->exception->class, 'ParseError');
    }

    public function explain(RuntimeContext $context): Explanation
    {
        $cause = 'PHP encountered a syntax error while parsing the code. The file contains ' .
            'invalid PHP syntax such as a missing or extra bracket, a typo in a keyword, ' .
            'or an unexpected token. The parser reports the location in the message.';

        $suggestions = [
            "Check the file and line reported: {$context->exception->file}:{$context->exception->line}",
            'Look for unmatched brackets, parentheses, or braces',
            'Verify there are no typos in keywords (e.g. functon vs function)',
            'Ensure strings are properly closed and escape sequences are valid',
            'If the error points to the end of file, look for a missing closing bracket or semicolon earlier',
        ];

        return new Explanation(
            message: $context->exception->message,
            cause: $cause,
            suggestions: $suggestions,
            confidence: 0.88,
            errorType: 'ParseError',
            location: "{$context->exception->file}:{$context->exception->line}",
        );
    }

    public function priority(): int
    {
        return 82;
    }
}
