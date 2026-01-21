<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Contracts;

use ClarityPHP\RuntimeInsight\DTO\Explanation;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;

/**
 * Engine that produces explanations from runtime context.
 */
interface ExplanationEngineInterface
{
    /**
     * Generate an explanation from runtime context.
     */
    public function explain(RuntimeContext $context): Explanation;
}

