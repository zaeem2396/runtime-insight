<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Contracts;

use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;

/**
 * Collects runtime signals to enrich RuntimeContext before analysis.
 * Collectors run after Context Builders and before Root Cause Analyzer.
 */
interface SignalCollectorInterface
{
    /**
     * Unique name for this collector (e.g. "query", "request").
     */
    public function getName(): string;

    /**
     * Whether this collector is enabled in the current environment.
     */
    public function isEnabled(): bool;

    /**
     * Collect signals and return payload to merge into context.
     * Return value is merged into CollectorsContext under getName().
     *
     * @return array<string, mixed>|null
     */
    public function collect(RuntimeContext $context): ?array;
}
