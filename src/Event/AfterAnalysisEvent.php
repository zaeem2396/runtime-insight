<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Event;

use ClarityPHP\RuntimeInsight\DTO\Explanation;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;

/**
 * Dispatched after explanation is produced (including root cause and pattern).
 * Listeners can read or post-process the explanation.
 */
final readonly class AfterAnalysisEvent
{
    public function __construct(
        public Explanation $explanation,
        public RuntimeContext $context,
    ) {}
}
