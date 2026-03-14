<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Log;

use ClarityPHP\RuntimeInsight\Contracts\LogParserInterface;
use ClarityPHP\RuntimeInsight\DTO\LogEntry;

use function file_exists;
use function file_get_contents;
use function is_readable;
use function preg_match;
use function preg_match_all;
use function trim;

/**
 * Parses Laravel-style log files (.ERROR lines) into LogEntry DTOs.
 */
final class LaravelLogParser implements LogParserInterface
{
    /**
     * @return array<int, LogEntry>
     */
    public function parseFile(string $path): array
    {
        if (! file_exists($path) || ! is_readable($path)) {
            return [];
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return [];
        }

        $pattern = '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] [^\n]*\.ERROR: (.+?)(?=\n\[\d{4}-\d{2}-\d{2}|\Z)/s';
        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER) === false) {
            return [];
        }

        $entries = [];
        foreach ($matches as $match) {
            $entries[] = $this->parseEntry($match[2], $match[1]);
        }

        return $entries;
    }

    /**
     * @param non-falsy-string $timestamp
     */
    private function parseEntry(string $raw, string $timestamp): LogEntry
    {
        $message = trim($raw);
        if (preg_match('/^(.+?)\s+\{\s*"/s', $raw, $msgMatch) === 1) {
            $message = trim($msgMatch[1]);
        }
        if ($message === '') {
            $message = 'Exception from log';
        }

        $file = 'unknown';
        $line = 0;
        if (preg_match('/\s+at\s+([^\s:]+(?:\.php)?):(\d+)/', $raw, $locMatch) === 1) {
            $file = $locMatch[1];
            $line = (int) $locMatch[2];
        }

        $exceptionClass = 'Exception';
        if (preg_match('/\(([A-Za-z_][A-Za-z0-9_]*)\s*(?:[:(]|at\b)/', $raw, $classMatch) === 1) {
            $exceptionClass = $classMatch[1];
        }

        return new LogEntry(
            message: $message,
            file: $file,
            line: $line,
            exceptionClass: $exceptionClass,
            timestamp: $timestamp,
        );
    }
}
