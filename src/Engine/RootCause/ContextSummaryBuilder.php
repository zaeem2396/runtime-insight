<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Engine\RootCause;

use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;

/**
 * Compact context summary: throw site plus optional call chain excerpt.
 */
final class ContextSummaryBuilder
{
    public function build(RuntimeContext $context, int $maxCallChainLines = 4): string
    {
        $base = $context->exception->file . ':' . $context->exception->line;
        $chain = $context->stackTrace->getCallChainSummary($maxCallChainLines);
        if ($chain === '') {
            return $base;
        }

        return $base . "\nCall chain (excerpt):\n" . $chain;
    }
}
