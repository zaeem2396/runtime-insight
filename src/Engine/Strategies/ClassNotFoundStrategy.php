<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Engine\Strategies;

use ClarityPHP\RuntimeInsight\Contracts\ExplanationStrategyInterface;
use ClarityPHP\RuntimeInsight\DTO\Explanation;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;

use function preg_match;
use function str_contains;

/**
 * Strategy for explaining class/interface/trait not found errors.
 *
 * Handles errors like:
 * - "Class 'X' not found"
 * - "Interface 'X' not found"
 * - "Trait 'X' not found"
 */
final class ClassNotFoundStrategy implements ExplanationStrategyInterface
{
    private const PATTERNS = [
        '/Class ["\']?([^"\']+)["\']? not found/' => 'class',
        '/Interface ["\']?([^"\']+)["\']? not found/' => 'interface',
        '/Trait ["\']?([^"\']+)["\']? not found/' => 'trait',
    ];

    public function supports(RuntimeContext $context): bool
    {
        $message = $context->exception->message;

        foreach (self::PATTERNS as $pattern => $type) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }

        return str_contains($message, 'not found') &&
               (str_contains($message, 'Class') ||
                str_contains($message, 'Interface') ||
                str_contains($message, 'Trait'));
    }

    public function explain(RuntimeContext $context): Explanation
    {
        $message = $context->exception->message;
        $details = $this->parseDetails($message);

        $cause = $this->buildCause($details, $context);
        $suggestions = $this->buildSuggestions($details, $context);

        return new Explanation(
            message: $message,
            cause: $cause,
            suggestions: $suggestions,
            confidence: 0.88,
            errorType: 'ClassNotFoundError',
            location: "{$context->exception->file}:{$context->exception->line}",
        );
    }

    public function priority(): int
    {
        return 80;
    }

    /**
     * @return array<string, string|null>
     */
    private function parseDetails(string $message): array
    {
        $details = [
            'type' => 'class',
            'name' => null,
        ];

        foreach (self::PATTERNS as $pattern => $type) {
            if (preg_match($pattern, $message, $matches)) {
                $details['type'] = $type;
                $details['name'] = $matches[1];

                break;
            }
        }

        return $details;
    }

    /**
     * @param array<string, string|null> $details
     */
    private function buildCause(array $details, RuntimeContext $context): string
    {
        $type = $details['type'] ?? 'class';
        $name = $details['name'] ?? 'Unknown';

        $typeLabel = match ($type) {
            'interface' => 'interface',
            'trait' => 'trait',
            default => 'class',
        };

        return "PHP cannot find the {$typeLabel} `{$name}`. This usually means the {$typeLabel} " .
            "file hasn't been loaded, the namespace is incorrect, or there's a typo in the {$typeLabel} name.";
    }

    /**
     * @param array<string, string|null> $details
     *
     * @return array<string>
     */
    private function buildSuggestions(array $details, RuntimeContext $context): array
    {
        $suggestions = [];
        $name = $details['name'] ?? '';

        // Check for common issues
        if (str_contains($name, '\\')) {
            $suggestions[] = 'Verify the namespace is correct and matches the directory structure';
            $suggestions[] = 'Check that the file exists at the expected PSR-4 autoload path';
        } else {
            $suggestions[] = 'Add the correct `use` statement at the top of the file';
            $suggestions[] = 'Use the fully qualified class name with namespace';
        }

        $suggestions[] = 'Run `composer dump-autoload` to regenerate the autoloader';
        $suggestions[] = 'Verify the class file exists and has the correct class name';
        $suggestions[] = 'Check for typos in the class name (PHP class names are case-insensitive but file systems may not be)';

        if (str_contains($name, 'Test') || str_contains($name, 'Mock')) {
            $suggestions[] = 'This might be a test class - ensure dev dependencies are installed with `composer install`';
        }

        return $suggestions;
    }
}
