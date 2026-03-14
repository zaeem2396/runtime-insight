<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\DTO;

/**
 * Single error/exception entry extracted from a log file.
 * Used by log parser and analyzer for summarization and grouping.
 */
final readonly class LogEntry
{
    public function __construct(
        public string $message,
        public string $file,
        public int $line,
        public string $exceptionClass,
        public ?string $timestamp = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'file' => $this->file,
            'line' => $this->line,
            'exception_class' => $this->exceptionClass,
            'timestamp' => $this->timestamp,
        ];
    }
}
