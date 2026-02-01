<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Contracts;

use ClarityPHP\RuntimeInsight\DTO\Explanation;

/**
 * Cache for explanation results (keyed by error signature).
 */
interface ExplanationCacheInterface
{
    /**
     * Get a cached explanation by key, or null if miss.
     */
    public function get(string $key): ?Explanation;

    /**
     * Store an explanation with the given key and TTL (seconds).
     */
    public function set(string $key, Explanation $explanation, int $ttl): void;
}
