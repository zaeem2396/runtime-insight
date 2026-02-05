<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Contracts;

use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use Throwable;

/**
 * Builds structured context from a throwable or from log-parsed entry.
 */
interface ContextBuilderInterface
{
    /**
     * Build a runtime context from a throwable.
     */
    public function build(Throwable $throwable): RuntimeContext;

    /**
     * Build a runtime context from a log entry (message, file, line).
     * Used when explaining from --log so "Where" shows the actual error location.
     */
    public function buildFromLogEntry(string $message, string $file, int $line): RuntimeContext;
}
