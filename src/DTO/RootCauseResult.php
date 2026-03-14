<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\DTO;

/**
 * Result of root cause analysis (primary cause, contributing factors, fix and prevention).
 *
 * @param array<string> $fixSuggestions
 * @param array<string> $preventionAdvice
 */
final readonly class RootCauseResult
{
    public function __construct(
        public string $primaryCause,
        public string $contributing = '',
        public string $contextSummary = '',
        public array $fixSuggestions = [],
        public array $preventionAdvice = [],
    ) {}

    public function isEmpty(): bool
    {
        return $this->primaryCause === '';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'primary_cause' => $this->primaryCause,
            'contributing' => $this->contributing,
            'context_summary' => $this->contextSummary,
            'fix_suggestions' => $this->fixSuggestions,
            'prevention_advice' => $this->preventionAdvice,
        ];
    }
}
