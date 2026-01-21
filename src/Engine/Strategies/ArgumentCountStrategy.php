<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Engine\Strategies;

use ClarityPHP\RuntimeInsight\Contracts\ExplanationStrategyInterface;
use ClarityPHP\RuntimeInsight\DTO\Explanation;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;

use function is_int;
use function is_string;
use function preg_match;
use function str_contains;

/**
 * Strategy for explaining argument count mismatch errors.
 *
 * Handles errors like:
 * - "Too few arguments to function X(), Y passed and exactly Z expected"
 * - "X() expects exactly Y arguments, Z given"
 */
final class ArgumentCountStrategy implements ExplanationStrategyInterface
{
    private const PATTERNS = [
        '/Too few arguments to function ([^,]+), (\d+) passed.* (?:exactly |at least )?(\d+) expected/' => 'too_few',
        '/Too many arguments to function ([^,]+), (\d+) passed.* (?:exactly |at most )?(\d+) expected/' => 'too_many',
        '/([^(]+)\(\) expects (?:exactly |at least )?(\d+) arguments?, (\d+) given/' => 'expects',
    ];

    public function supports(RuntimeContext $context): bool
    {
        $message = $context->exception->message;

        foreach (self::PATTERNS as $pattern => $type) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }

        return str_contains($message, 'Too few arguments') ||
               str_contains($message, 'Too many arguments') ||
               str_contains($message, 'expects exactly') ||
               str_contains($message, 'expects at least');
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
            confidence: 0.92,
            errorType: 'ArgumentCountError',
            location: "{$context->exception->file}:{$context->exception->line}",
        );
    }

    public function priority(): int
    {
        return 85;
    }

    /**
     * @return array<string, string|int|null>
     */
    private function parseDetails(string $message): array
    {
        $details = [
            'type' => 'unknown',
            'function' => null,
            'passed' => null,
            'expected' => null,
        ];

        foreach (self::PATTERNS as $pattern => $type) {
            if (preg_match($pattern, $message, $matches)) {
                $details['type'] = $type;
                $details['function'] = $matches[1];

                if ($type === 'expects') {
                    $details['expected'] = (int) $matches[2];
                    $details['passed'] = (int) $matches[3];
                } else {
                    $details['passed'] = (int) $matches[2];
                    $details['expected'] = (int) $matches[3];
                }

                break;
            }
        }

        return $details;
    }

    /**
     * @param array<string, string|int|null> $details
     */
    private function buildCause(array $details, RuntimeContext $context): string
    {
        $function = $details['function'] ?? 'the function';
        $passed = $details['passed'] ?? 'an incorrect number of';
        $expected = $details['expected'] ?? 'the required number of';

        return match ($details['type']) {
            'too_few' => "The function `{$function}` requires at least {$expected} argument(s), " .
                "but only {$passed} were provided. Some required parameters are missing.",
            'too_many' => "The function `{$function}` accepts at most {$expected} argument(s), " .
                "but {$passed} were provided. Too many arguments were passed.",
            'expects' => "The function `{$function}` expects {$expected} argument(s), " .
                "but {$passed} were given.",
            default => "The function was called with {$passed} arguments, but {$expected} were expected.",
        };
    }

    /**
     * @param array<string, string|int|null> $details
     *
     * @return array<string>
     */
    private function buildSuggestions(array $details, RuntimeContext $context): array
    {
        $suggestions = [];
        $function = $details['function'] ?? '';

        $passed = is_int($details['passed']) ? $details['passed'] : 0;
        $expected = is_int($details['expected']) ? $details['expected'] : 0;

        if ($details['type'] === 'too_few' || ($details['type'] === 'expects' && $passed < $expected)) {
            $suggestions[] = 'Add the missing required arguments to the function call';
            $suggestions[] = 'Check the function signature to see which parameters are required';

            if (is_string($function) && str_contains($function, '::')) {
                $suggestions[] = 'This might be a method call - verify you have the correct method signature';
            }
        } else {
            $suggestions[] = 'Remove the extra arguments from the function call';
            $suggestions[] = 'Verify you are calling the correct function/method';
        }

        $suggestions[] = 'Check your IDE for autocomplete hints on required parameters';
        $suggestions[] = 'Review the function documentation for parameter requirements';

        return $suggestions;
    }
}
