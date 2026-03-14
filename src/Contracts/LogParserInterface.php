<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Contracts;

use ClarityPHP\RuntimeInsight\DTO\LogEntry;

/**
 * Parses a log file and returns structured error entries.
 */
interface LogParserInterface
{
    /**
     * Parse a log file and return all error/exception entries.
     *
     * @return array<int, LogEntry>
     */
    public function parseFile(string $path): array;
}
