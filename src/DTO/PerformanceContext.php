<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\DTO;

/**
 * Memory and performance context at time of error.
 */
final readonly class PerformanceContext
{
    public function __construct(
        public int $peakMemoryBytes = 0,
        public float $scriptRuntimeSeconds = 0.0,
    ) {}

    public function getPeakMemoryFormatted(): string
    {
        if ($this->peakMemoryBytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $i = (int) floor(log($this->peakMemoryBytes, 1024));
        $i = min($i, count($units) - 1);

        return round($this->peakMemoryBytes / 1024 ** $i, 2) . ' ' . $units[$i];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'peak_memory_bytes' => $this->peakMemoryBytes,
            'peak_memory_formatted' => $this->getPeakMemoryFormatted(),
            'script_runtime_seconds' => $this->scriptRuntimeSeconds,
        ];
    }

    public function isEmpty(): bool
    {
        return $this->peakMemoryBytes <= 0 && $this->scriptRuntimeSeconds <= 0.0;
    }
}
