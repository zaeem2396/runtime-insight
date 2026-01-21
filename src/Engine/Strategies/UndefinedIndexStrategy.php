<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Engine\Strategies;

use ClarityPHP\RuntimeInsight\Contracts\ExplanationStrategyInterface;
use ClarityPHP\RuntimeInsight\DTO\Explanation;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;

use function preg_match;
use function str_contains;

/**
 * Strategy for explaining undefined array index/key errors.
 *
 * Handles errors like:
 * - "Undefined array key X"
 * - "Undefined index: X"
 * - "Undefined offset: X"
 */
final class UndefinedIndexStrategy implements ExplanationStrategyInterface
{
    private const PATTERNS = [
        '/Undefined array key ["\']?(\w+)["\']?/' => 'array_key',
        '/Undefined index:?\s*["\']?(\w+)["\']?/' => 'index',
        '/Undefined offset:?\s*(\d+)/' => 'offset',
        '/Cannot access offset of type .+ on .+/' => 'type_offset',
    ];

    public function supports(RuntimeContext $context): bool
    {
        $message = $context->exception->message;

        foreach (self::PATTERNS as $pattern => $type) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }

        return str_contains($message, 'Undefined array key') ||
               str_contains($message, 'Undefined index') ||
               str_contains($message, 'Undefined offset');
    }

    public function explain(RuntimeContext $context): Explanation
    {
        $message = $context->exception->message;
        $keyName = $this->extractKeyName($message);
        $errorType = $this->detectErrorType($message);

        $cause = $this->buildCause($errorType, $keyName, $context);
        $suggestions = $this->buildSuggestions($errorType, $keyName, $context);

        return new Explanation(
            message: $message,
            cause: $cause,
            suggestions: $suggestions,
            confidence: 0.88,
            errorType: 'UndefinedIndex',
            location: "{$context->exception->file}:{$context->exception->line}",
        );
    }

    public function priority(): int
    {
        return 95;
    }

    private function extractKeyName(string $message): ?string
    {
        foreach (self::PATTERNS as $pattern => $type) {
            if (preg_match($pattern, $message, $matches)) {
                return $matches[1] ?? null;
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

    private function buildCause(string $errorType, ?string $keyName, RuntimeContext $context): string
    {
        $keyInfo = $keyName !== null ? " `{$keyName}`" : '';

        return match ($errorType) {
            'array_key' => "You tried to access array key{$keyInfo} that does not exist in the array. " .
                'The array either does not contain this key, or the key name is misspelled.',
            'index' => "The array index{$keyInfo} you tried to access does not exist. " .
                'This usually means the data structure does not contain the expected element.',
            'offset' => "The numeric offset{$keyInfo} is out of bounds for this array. " .
                'The array has fewer elements than expected.',
            'type_offset' => 'You tried to use an invalid type as an array offset. ' .
                'Array keys must be integers or strings.',
            default => "The array key or index{$keyInfo} does not exist in the array you are trying to access.",
        };
    }

    /**
     * @return array<string>
     */
    private function buildSuggestions(string $errorType, ?string $keyName, RuntimeContext $context): array
    {
        $suggestions = [];

        if ($keyName !== null) {
            $suggestions[] = "Check if the key exists using `isset(\$array['{$keyName}'])` or `array_key_exists('{$keyName}', \$array)`";
            $suggestions[] = "Use the null coalescing operator: `\$array['{$keyName}'] ?? 'default'`";
        } else {
            $suggestions[] = 'Check if the key exists using `isset()` or `array_key_exists()` before accessing';
            $suggestions[] = 'Use the null coalescing operator `??` to provide a default value';
        }

        // Context-specific suggestions
        if ($context->sourceContext->codeSnippet !== '') {
            $snippet = $context->sourceContext->codeSnippet;

            if (str_contains($snippet, '$_POST') || str_contains($snippet, '$_GET') || str_contains($snippet, '$_REQUEST')) {
                $suggestions[] = 'When accessing superglobals, always validate input existence. Consider using filter_input() or a request object';
            }

            if (str_contains($snippet, 'json_decode')) {
                $suggestions[] = 'The JSON might not contain the expected structure. Validate the decoded data before accessing nested keys';
            }

            if (str_contains($snippet, 'config(') || str_contains($snippet, 'env(')) {
                $suggestions[] = 'Ensure the configuration key is defined in your config files or .env file';
            }
        }

        $suggestions[] = 'Verify the data source is returning the expected structure';

        return $suggestions;
    }
}
