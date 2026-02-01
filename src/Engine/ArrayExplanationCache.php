<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Engine;

use ClarityPHP\RuntimeInsight\Contracts\ExplanationCacheInterface;
use ClarityPHP\RuntimeInsight\DTO\Explanation;

/**
 * In-memory cache for explanations (per-request, no TTL enforcement).
 */
final class ArrayExplanationCache implements ExplanationCacheInterface
{
    /**
     * @var array<string, array{explanation: Explanation, expires_at: int}>
     */
    private array $store = [];

    public function get(string $key): ?Explanation
    {
        $entry = $this->store[$key] ?? null;

        if ($entry === null) {
            return null;
        }

        if ($entry['expires_at'] > 0 && $entry['expires_at'] < time()) {
            unset($this->store[$key]);

            return null;
        }

        return $entry['explanation'];
    }

    public function set(string $key, Explanation $explanation, int $ttl): void
    {
        $this->store[$key] = [
            'explanation' => $explanation,
            'expires_at' => $ttl > 0 ? time() + $ttl : 0,
        ];
    }
}
