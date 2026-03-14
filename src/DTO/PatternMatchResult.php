<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\DTO;

/**
 * Result of pattern analysis (e.g. N+1, validation, eager loading).
 */
final readonly class PatternMatchResult
{
    /**
     * @param array<string> $suggestions
     */
    public function __construct(
        public string $patternName,
        public string $summary = '',
        public string $location = '',
        public array $suggestions = [],
    ) {}

    public function isEmpty(): bool
    {
        return $this->patternName === '';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'pattern_name' => $this->patternName,
            'summary' => $this->summary,
            'location' => $this->location,
            'suggestions' => $this->suggestions,
        ];
    }
}
