<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\DTO;

/**
 * Database/query context at time of error (e.g. recent queries).
 */
final readonly class DatabaseContext
{
    /**
     * @param array<string> $recentQueries List of recent query strings (SQL or description)
     */
    public function __construct(
        public array $recentQueries = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'recent_queries' => $this->recentQueries,
        ];
    }

    public function isEmpty(): bool
    {
        return $this->recentQueries === [];
    }
}
