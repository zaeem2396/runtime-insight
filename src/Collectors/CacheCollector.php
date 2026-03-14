<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Collectors;

use ClarityPHP\RuntimeInsight\Contracts\SignalCollectorInterface;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;

/**
 * Placeholder for cache signals (hits/misses, keys involved).
 * Framework-specific implementations can provide cache context.
 */
final class CacheCollector implements SignalCollectorInterface
{
    public function getName(): string
    {
        return 'cache';
    }

    public function isEnabled(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function collect(RuntimeContext $context): ?array
    {
        return null;
    }
}
