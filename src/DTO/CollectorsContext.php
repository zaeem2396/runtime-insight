<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\DTO;

/**
 * Optional payload from Signal Collectors, attached to RuntimeContext.
 *
 * @param array<string, array<string, mixed>> $signals Map of collector name => payload
 */
final readonly class CollectorsContext
{
    public function __construct(
        public array $signals = [],
    ) {}

    public function isEmpty(): bool
    {
        return $this->signals === [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['signals' => $this->signals];
    }
}
