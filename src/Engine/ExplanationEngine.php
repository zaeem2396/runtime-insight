<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Engine;

use ClarityPHP\RuntimeInsight\Config;
use ClarityPHP\RuntimeInsight\Contracts\AIProviderInterface;
use ClarityPHP\RuntimeInsight\Contracts\ExplanationEngineInterface;
use ClarityPHP\RuntimeInsight\Contracts\ExplanationStrategyInterface;
use ClarityPHP\RuntimeInsight\DTO\Explanation;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;

use function preg_match;
use function str_contains;
use function strrpos;
use function substr;
use function usort;

/**
 * Engine that produces explanations from runtime context.
 *
 * Uses a chain of strategies (rule-based first, then AI if enabled).
 */
final class ExplanationEngine implements ExplanationEngineInterface
{
    /**
     * @var array<ExplanationStrategyInterface>
     */
    private array $strategies = [];

    public function __construct(
        private readonly Config $config,
        private readonly ?AIProviderInterface $aiProvider = null,
    ) {}

    /**
     * Register an explanation strategy.
     */
    public function addStrategy(ExplanationStrategyInterface $strategy): self
    {
        $this->strategies[] = $strategy;

        // Sort by priority (highest first)
        usort(
            $this->strategies,
            static fn(ExplanationStrategyInterface $a, ExplanationStrategyInterface $b): int => $b->priority() <=> $a->priority(),
        );

        return $this;
    }

    /**
     * Generate an explanation from runtime context.
     */
    public function explain(RuntimeContext $context): Explanation
    {
        $explanation = null;

        // Try rule-based strategies first
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($context)) {
                $explanation = $strategy->explain($context);

                break;
            }
        }

        // Fall back to AI if enabled and available
        if ($explanation === null && $this->config->isAIEnabled() && $this->aiProvider !== null && $this->aiProvider->isAvailable()) {
            $aiExplanation = $this->aiProvider->analyze($context);
            if (! $aiExplanation->isEmpty()) {
                $explanation = $aiExplanation;
            }
        }

        // Fall back to descriptive fallback when no strategy matched or AI returned empty
        if ($explanation === null) {
            $explanation = $this->buildFallbackExplanation($context);
        }

        return $this->enrichWithCodeContext($explanation, $context);
    }

    /**
     * Get all registered strategies.
     *
     * @return array<ExplanationStrategyInterface>
     */
    public function getStrategies(): array
    {
        return $this->strategies;
    }

    /**
     * Attach code snippet and call-site location so output shows which code block to update.
     */
    private function enrichWithCodeContext(Explanation $explanation, RuntimeContext $context): Explanation
    {
        $snippet = $context->sourceContext->codeSnippet ?? '';
        $callSite = $this->extractCallSiteFromMessage($context->exception->message)
            ?? $this->getCallSiteFromStackTrace($context);

        if ($snippet === '' && $callSite === null) {
            return $explanation;
        }

        return $explanation->withCodeContext($snippet, $callSite);
    }

    /**
     * Get call site from stack trace (caller frame) when available.
     */
    private function getCallSiteFromStackTrace(RuntimeContext $context): ?string
    {
        $frames = $context->stackTrace->frames;
        if (isset($frames[1])) {
            $caller = $frames[1];
            $loc = $caller->getLocation();

            return $loc !== '' ? $loc : null;
        }

        return null;
    }

    /**
     * Extract "called in /path/file.php on line N" from PHP exception message.
     */
    private function extractCallSiteFromMessage(string $message): ?string
    {
        if (preg_match('/called in (.+?) on line (\d+)/', $message, $matches) === 1) {
            $file = trim($matches[1]);
            $line = (int) $matches[2];

            return "{$file}:{$line}";
        }

        return null;
    }

    /**
     * Build a descriptive fallback explanation when no strategy matches.
     * Uses per-exception-class descriptions so all error types get a clear explanation (fixes #25).
     *
     * @return array{cause: string, suggestions: array<string>}
     */
    private static function getDescriptiveFallback(string $exceptionClass, string $file, int $line): array
    {
        $shortClass = str_contains($exceptionClass, '\\')
            ? substr($exceptionClass, (int) strrpos($exceptionClass, '\\') + 1)
            : $exceptionClass;
        $locationHint = "Look at the code near {$file}:{$line}";

        $map = [
            'RuntimeException' => [
                'cause' => 'A runtime exception occurred. This usually indicates a logical or environmental error that was detected during execution (e.g. invalid state, missing resource).',
                'suggestions' => ['Read the exception message for the specific reason', 'Check preconditions and environment (files, config, services)', 'Review the stack trace for the call that triggered the error'],
            ],
            'LogicException' => [
                'cause' => 'A logic exception was thrown. This indicates a bug in program logic (e.g. calling a method when the object is in an invalid state).',
                'suggestions' => ['Ensure preconditions are met before the operation', 'Add guards or validation for the invalid state', 'Review the exception message and stack trace for the violating call'],
            ],
            'InvalidArgumentException' => [
                'cause' => 'An invalid argument was passed to a function or method. The value does not meet the expected contract (type may be correct but value or format is not).',
                'suggestions' => ['Check the argument value at the call site', 'Validate input before passing it', 'Consult the method documentation for allowed values'],
            ],
            'DomainException' => [
                'cause' => 'A domain exception occurred. The operation is not valid in the current domain or context.',
                'suggestions' => ['Verify the operation is valid in this context', 'Check business rules or domain invariants', 'Review the exception message for the violated constraint'],
            ],
            'RangeException' => [
                'cause' => 'A value was outside the allowed range. The operation encountered a value that is not within the valid range (e.g. index out of bounds).',
                'suggestions' => ['Validate indices and ranges before use', 'Check that values are within expected bounds', 'Add bounds checking or use safe accessors'],
            ],
            'LengthException' => [
                'cause' => 'A length-related error occurred. A string, array, or other value has an invalid length for the operation.',
                'suggestions' => ['Check length constraints before the operation', 'Validate minimum/maximum length', 'Review the exception message for the required length'],
            ],
            'OutOfBoundsException' => [
                'cause' => 'An out-of-bounds access was attempted. An index or key was used that does not exist in the collection.',
                'suggestions' => ['Check that the index or key exists before access', 'Use isset() or array_key_exists() for arrays', 'Provide a default or handle the missing case'],
            ],
            'UnexpectedValueException' => [
                'cause' => 'An unexpected value was encountered. A function returned a value that was not expected (e.g. out of a set of allowed values).',
                'suggestions' => ['Validate return values from external code or APIs', 'Handle all possible return cases', 'Add assertions or checks for expected values'],
            ],
            'ErrorException' => [
                'cause' => 'A PHP error was converted to an exception (e.g. notice, warning). The error message and line indicate what went wrong.',
                'suggestions' => ['Read the error message for the underlying PHP error', 'Fix the cause (e.g. undefined variable, deprecated usage)', 'Check the file and line reported in the exception'],
            ],
            'Exception' => [
                'cause' => 'An exception was thrown. The message describes what went wrong; the stack trace shows where it originated.',
                'suggestions' => ['Review the exception message for details', 'Check the stack trace for the throwing location', $locationHint],
            ],
        ];

        foreach ($map as $key => $data) {
            if ($shortClass === $key || str_contains($exceptionClass, $key)) {
                return $data;
            }
        }

        return [
            'cause' => "An exception of type {$exceptionClass} was thrown. The message and stack trace provide the details.",
            'suggestions' => [
                'Review the exception message for the specific reason',
                'Check the stack trace for the call that triggered the error',
                $locationHint,
            ],
        ];
    }

    /**
     * Build a descriptive fallback explanation when no strategy matches.
     */
    private function buildFallbackExplanation(RuntimeContext $context): Explanation
    {
        $class = $context->exception->class;
        $file = $context->exception->file;
        $line = $context->exception->line;
        $fallback = self::getDescriptiveFallback($class, $file, $line);

        return new Explanation(
            message: $context->exception->message,
            cause: $fallback['cause'],
            suggestions: $fallback['suggestions'],
            confidence: 0.5,
            errorType: $class,
            location: "{$file}:{$line}",
        );
    }
}
