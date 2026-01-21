<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Contracts;

use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use Throwable;

/**
 * Builds structured context from a throwable.
 */
interface ContextBuilderInterface
{
    /**
     * Build a runtime context from a throwable.
     */
    public function build(Throwable $throwable): RuntimeContext;
}

