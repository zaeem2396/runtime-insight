<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Log;

use ClarityPHP\RuntimeInsight\DTO\LogEntry;

/**
 * Builds a normalized signature for an error (for grouping and deduplication).
 * Format: exceptionClass|message|file:line
 */
final class ErrorSignature
{
    /**
     * Build a signature string for grouping: exceptionClass|message|file:line
     */
    public static function fromEntry(LogEntry $entry): string
    {
        return $entry->exceptionClass . '|' . $entry->message . '|' . $entry->file . ':' . $entry->line;
    }
}
