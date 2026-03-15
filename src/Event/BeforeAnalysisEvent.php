<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Event;

use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;

/**
 * Dispatched after context is built and enriched, before the explanation engine runs.
 * Listeners can read or modify context (e.g. add custom metadata).
 */
final readonly class BeforeAnalysisEvent
{
    public function __construct(
        public RuntimeContext $context,
    ) {}
}
