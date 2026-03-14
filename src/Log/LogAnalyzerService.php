<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Log;

use ClarityPHP\RuntimeInsight\Contracts\LogParserInterface;
use ClarityPHP\RuntimeInsight\DTO\LogSummaryResult;

use function array_slice;
use function count;
use function usort;

/**
 * Analyzes a log file: parses entries and groups by signature for summary.
 * Used by runtime:analyze to show total errors and top failures.
 */
final class LogAnalyzerService
{
    public function __construct(
        private readonly LogParserInterface $parser,
        private readonly int $topFailuresLimit = 10,
    ) {}

    /**
     * Parse log file and return summary with total count and top failures by signature.
     */
    public function analyze(string $logPath): LogSummaryResult
    {
        $entries = $this->parser->parseFile($logPath);
        $total = count($entries);

        if ($total === 0) {
            return new LogSummaryResult(logPath: $logPath, totalErrors: 0);
        }

        $bySignature = [];
        foreach ($entries as $entry) {
            $sig = ErrorSignature::fromEntry($entry);
            if (! isset($bySignature[$sig])) {
                $bySignature[$sig] = [];
            }
            $bySignature[$sig][] = $entry;
        }

        $top = [];
        foreach ($bySignature as $signature => $list) {
            $top[] = [
                'signature' => $signature,
                'count' => count($list),
                'sample' => $list[0],
            ];
        }
        usort($top, static fn(array $a, array $b): int => $b['count'] <=> $a['count']);
        $top = array_slice($top, 0, $this->topFailuresLimit);

        return new LogSummaryResult(
            logPath: $logPath,
            totalErrors: $total,
            entriesBySignature: $bySignature,
            topFailures: $top,
        );
    }
}
