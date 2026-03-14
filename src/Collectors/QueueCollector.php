<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Collectors;

use ClarityPHP\RuntimeInsight\Contracts\SignalCollectorInterface;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;

/**
 * Placeholder for queue job signals (e.g. failed job, job name).
 * Framework-specific implementations can provide queue context.
 */
final class QueueCollector implements SignalCollectorInterface
{
    public function getName(): string
    {
        return 'queue';
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
