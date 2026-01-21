<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Contracts;

use ClarityPHP\RuntimeInsight\DTO\Explanation;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;

/**
 * Strategy for explaining specific types of errors.
 */
interface ExplanationStrategyInterface
{
    /**
     * Check if this strategy can handle the given context.
     */
    public function supports(RuntimeContext $context): bool;

    /**
     * Generate an explanation for the context.
     */
    public function explain(RuntimeContext $context): Explanation;

    /**
     * Get the priority of this strategy.
     * Higher values are checked first.
     */
    public function priority(): int;
}

