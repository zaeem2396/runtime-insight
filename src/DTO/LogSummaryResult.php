<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\DTO;

/**
 * Result of log analysis: total count and grouped entries.
 */
final readonly class LogSummaryResult
{
    /**
     * @param array<string, array<int, LogEntry>> $entriesBySignature
     * @param array<int, array{signature: string, count: int, sample: LogEntry}> $topFailures
     */
    public function __construct(
        public string $logPath,
        public int $totalErrors,
        public array $entriesBySignature = [],
        public array $topFailures = [],
    ) {}

    public function isEmpty(): bool
    {
        return $this->totalErrors === 0;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $top = [];
        foreach ($this->topFailures as $t) {
            $sample = $t['sample'];
            $top[] = [
                'signature' => $t['signature'],
                'count' => $t['count'],
                'sample' => $sample->toArray(),
            ];
        }

        $bySig = [];
        foreach ($this->entriesBySignature as $sig => $entries) {
            $bySig[$sig] = array_map(static fn(LogEntry $e) => $e->toArray(), $entries);
        }

        return [
            'log_path' => $this->logPath,
            'total_errors' => $this->totalErrors,
            'entries_by_signature' => $bySig,
            'top_failures' => $top,
        ];
    }
}
