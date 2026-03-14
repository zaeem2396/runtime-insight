<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Collectors;

use ClarityPHP\RuntimeInsight\Contracts\SignalCollectorInterface;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;

/**
 * Collects memory usage from performance context (peak memory).
 */
final class MemoryCollector implements SignalCollectorInterface
{
    public function getName(): string
    {
        return 'memory';
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
        if ($context->performanceContext === null || $context->performanceContext->isEmpty()) {
            return null;
        }

        return [
            'peak_memory_bytes' => $context->performanceContext->peakMemoryBytes,
            'script_runtime_seconds' => $context->performanceContext->scriptRuntimeSeconds,
        ];
    }
}
