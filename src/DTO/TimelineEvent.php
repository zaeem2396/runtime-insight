<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\DTO;

/**
 * Single event in a runtime timeline (e.g. request started, query, exception).
 * Used by TimelineService and runtime:timeline output.
 */
final readonly class TimelineEvent
{
    public function __construct(
        public float $relativeSeconds,
        public string $type,
        public string $label,
        public string $detail = '',
    ) {}

    /**
     * Array representation for serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'relative_seconds' => $this->relativeSeconds,
            'type' => $this->type,
            'label' => $this->label,
            'detail' => $this->detail,
        ];
    }
}
