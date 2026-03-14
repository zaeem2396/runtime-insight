<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\DTO;

/**
 * Result of building a runtime timeline (ordered events with relative times).
 * Returned by TimelineService::buildFromLog(). Events are in chronological order.
 */
final readonly class TimelineResult
{
    /**
     * @param array<int, TimelineEvent> $events
     */
    public function __construct(
        public string $logPath,
        public array $events = [],
    ) {}

    public function isEmpty(): bool
    {
        return $this->events === [];
    }

    /** Number of events in the timeline. */
    public function eventCount(): int
    {
        return count($this->events);
    }

    /**
     * Array representation for API or serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'log_path' => $this->logPath,
            'events' => array_map(static fn(TimelineEvent $e) => $e->toArray(), $this->events),
        ];
    }
}
