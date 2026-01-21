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
 * Strategy for explaining type mismatch errors.
 *
 * Handles errors like:
 * - "Argument #X must be of type Y, Z given"
 * - "Return value must be of type X, Y returned"
 * - "Cannot assign X to property Y of type Z"
 */
final class TypeErrorStrategy implements ExplanationStrategyInterface
{
    private const PATTERNS = [
        '/Argument #(\d+)(?: \((\$\w+)\))? must be of type (\w+(?:\|[\w\\\\]+)*), (\w+)(?: \$\w+)? given/' => 'argument',
        '/Return value(?: of function \w+\(\))? must be of type (\w+(?:\|[\w\\\\]+)*), (\w+) returned/' => 'return',
        '/Cannot assign (\w+) to property .+::\$(\w+) of type (\w+)/' => 'property',
        '/(\w+)::(\w+)\(\): Argument #(\d+).+ must be of type (\w+), (\w+) given/' => 'method_argument',
    ];

    public function supports(RuntimeContext $context): bool
    {
        // Must be a TypeError
        if (! str_contains($context->exception->class, 'TypeError')) {
            return false;
        }

        $message = $context->exception->message;

        // Exclude null pointer errors (handled by NullPointerStrategy)
        if (str_contains($message, 'on null')) {
            return false;
        }

        foreach (self::PATTERNS as $pattern => $type) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }

        return str_contains($message, 'must be of type') ||
               str_contains($message, 'Cannot assign');
    }

    public function explain(RuntimeContext $context): Explanation
    {
        $message = $context->exception->message;
        $errorDetails = $this->parseErrorDetails($message);

        $cause = $this->buildCause($errorDetails, $context);
        $suggestions = $this->buildSuggestions($errorDetails, $context);

        return new Explanation(
            message: $message,
            cause: $cause,
            suggestions: $suggestions,
            confidence: 0.90,
            errorType: 'TypeError',
            location: "{$context->exception->file}:{$context->exception->line}",
        );
    }

    public function priority(): int
    {
        return 90;
    }

    /**
     * @return array<string, string|null>
     */
    private function parseErrorDetails(string $message): array
    {
        $details = [
            'type' => 'unknown',
            'expected' => null,
            'actual' => null,
            'argument' => null,
            'property' => null,
        ];

        // Argument type error
        if (preg_match('/Argument #(\d+)(?: \((\$\w+)\))? must be of type ([^,]+), (\w+)/', $message, $matches)) {
            $details['type'] = 'argument';
            $details['argument'] = $matches[1];
            $details['expected'] = $matches[3];
            $details['actual'] = $matches[4];

            return $details;
        }

        // Return type error
        if (preg_match('/Return value.* must be of type ([^,]+), (\w+) returned/', $message, $matches)) {
            $details['type'] = 'return';
            $details['expected'] = $matches[1];
            $details['actual'] = $matches[2];

            return $details;
        }

        // Property type error
        if (preg_match('/Cannot assign (\w+) to property .+::\$(\w+) of type (\w+)/', $message, $matches)) {
            $details['type'] = 'property';
            $details['actual'] = $matches[1];
            $details['property'] = $matches[2];
            $details['expected'] = $matches[3];

            return $details;
        }

        return $details;
    }

    /**
     * @param array<string, string|null> $details
     */
    private function buildCause(array $details, RuntimeContext $context): string
    {
        $expected = $details['expected'] ?? 'unknown';
        $actual = $details['actual'] ?? 'unknown';

        return match ($details['type']) {
            'argument' => "Function or method expected argument #{$details['argument']} to be of type `{$expected}`, " .
                "but received `{$actual}` instead. This is a type mismatch that PHP's strict type checking caught.",
            'return' => "The function or method is declared to return `{$expected}`, but it actually returned `{$actual}`. " .
                'This violates the return type declaration.',
            'property' => "Cannot assign a value of type `{$actual}` to property `\${$details['property']}` " .
                "which is typed as `{$expected}`. The types are incompatible.",
            default => "A type mismatch occurred. Expected `{$expected}` but got `{$actual}`.",
        };
    }

    /**
     * @param array<string, string|null> $details
     *
     * @return array<string>
     */
    private function buildSuggestions(array $details, RuntimeContext $context): array
    {
        $suggestions = [];
        $expected = $details['expected'] ?? '';
        $actual = $details['actual'] ?? '';

        // Common type conversion suggestions
        if ($actual === 'null' && $expected !== '') {
            $suggestions[] = "Make the parameter/property nullable by adding `?` before the type: `?{$expected}`";
            $suggestions[] = 'Provide a non-null value or set a default value';
        }

        if ($actual === 'string' && str_contains($expected, 'int')) {
            $suggestions[] = 'Convert the string to integer using `(int)` cast or `intval()`';
        }

        if ($actual === 'int' && str_contains($expected, 'string')) {
            $suggestions[] = 'Convert the integer to string using `(string)` cast or `strval()`';
        }

        if ($actual === 'array' && str_contains($expected, 'object')) {
            $suggestions[] = 'Convert the array to object using `(object)` cast or instantiate the expected class';
        }

        // General suggestions
        $suggestions[] = 'Verify the data source is returning the expected type';
        $suggestions[] = 'Add type validation before passing the value';

        if ($details['type'] === 'return') {
            $suggestions[] = 'Ensure all code paths return the correct type';
            $suggestions[] = 'Check for early returns that might return a different type';
        }

        return $suggestions;
    }
}
