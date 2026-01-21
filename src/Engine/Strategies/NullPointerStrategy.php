<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Engine\Strategies;

use ClarityPHP\RuntimeInsight\Contracts\ExplanationStrategyInterface;
use ClarityPHP\RuntimeInsight\DTO\Explanation;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use TypeError;

use function preg_match;
use function str_contains;

/**
 * Strategy for explaining null pointer / null reference errors.
 *
 * Handles errors like:
 * - "Call to a member function X() on null"
 * - "Attempt to read property X on null"
 * - "Cannot access property X on null"
 */
final class NullPointerStrategy implements ExplanationStrategyInterface
{
    private const PATTERNS = [
        '/Call to a member function (\w+)\(\) on null/' => 'method_call',
        '/Attempt to read property "?(\w+)"? on null/' => 'property_read',
        '/Cannot access property (\w+) on null/' => 'property_access',
        '/Attempt to assign property "?(\w+)"? on null/' => 'property_assign',
    ];

    public function supports(RuntimeContext $context): bool
    {
        $message = $context->exception->message;

        // Check if it's a TypeError with null-related message
        if (! str_contains($context->exception->class, 'TypeError') &&
            ! str_contains($context->exception->class, 'Error')) {
            return false;
        }

        foreach (self::PATTERNS as $pattern => $type) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }

        return str_contains($message, 'on null');
    }

    public function explain(RuntimeContext $context): Explanation
    {
        $message = $context->exception->message;
        $memberName = $this->extractMemberName($message);
        $errorType = $this->detectErrorType($message);

        $cause = $this->buildCause($errorType, $memberName, $context);
        $suggestions = $this->buildSuggestions($errorType, $memberName, $context);

        return new Explanation(
            message: $message,
            cause: $cause,
            suggestions: $suggestions,
            confidence: 0.85,
            errorType: 'NullPointerError',
            location: "{$context->exception->file}:{$context->exception->line}",
        );
    }

    public function priority(): int
    {
        return 100;
    }

    private function extractMemberName(string $message): ?string
    {
        foreach (self::PATTERNS as $pattern => $type) {
            if (preg_match($pattern, $message, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    private function detectErrorType(string $message): string
    {
        foreach (self::PATTERNS as $pattern => $type) {
            if (preg_match($pattern, $message)) {
                return $type;
            }
        }

        return 'unknown';
    }

    private function buildCause(string $errorType, ?string $memberName, RuntimeContext $context): string
    {
        $baseMessage = 'A variable that was expected to contain an object is actually null.';

        $causes = match ($errorType) {
            'method_call' => $memberName !== null
                ? "You tried to call the method `{$memberName}()` on a variable that is null. {$baseMessage}"
                : "You tried to call a method on a variable that is null. {$baseMessage}",
            'property_read' => $memberName !== null
                ? "You tried to read the property `{$memberName}` from a variable that is null. {$baseMessage}"
                : "You tried to read a property from a variable that is null. {$baseMessage}",
            'property_access' => $memberName !== null
                ? "You tried to access the property `{$memberName}` on a variable that is null. {$baseMessage}"
                : "You tried to access a property on a variable that is null. {$baseMessage}",
            'property_assign' => $memberName !== null
                ? "You tried to assign a value to the property `{$memberName}` on a variable that is null. {$baseMessage}"
                : "You tried to assign a property on a variable that is null. {$baseMessage}",
            default => $baseMessage,
        };

        return $causes . ' This often happens when a database query returns no results, a method returns null unexpectedly, or optional data is accessed without checking.';
    }

    /**
     * @return array<string>
     */
    private function buildSuggestions(string $errorType, ?string $memberName, RuntimeContext $context): array
    {
        $suggestions = [
            'Check if the variable is null before accessing it using `if ($variable !== null)`',
            'Use the null coalescing operator `??` to provide a default value',
            'Use the nullsafe operator `?->` for optional chaining (PHP 8+)',
        ];

        // Add context-specific suggestions based on source code analysis
        if ($context->sourceContext->codeSnippet !== '') {
            $snippet = $context->sourceContext->codeSnippet;

            if (str_contains($snippet, '->find(') || str_contains($snippet, '->first(')) {
                $suggestions[] = 'The database query might be returning null. Use `findOrFail()` or check if the result exists';
            }

            if (str_contains($snippet, 'auth()') || str_contains($snippet, 'user()')) {
                $suggestions[] = 'Ensure the user is authenticated. Add authentication middleware or check `auth()->check()`';
            }

            if (str_contains($snippet, 'request()') || str_contains($snippet, '$request')) {
                $suggestions[] = 'Validate that the expected request data is present before accessing it';
            }
        }

        return $suggestions;
    }
}
