<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Log;

use ClarityPHP\RuntimeInsight\Contracts\LogParserInterface;
use ClarityPHP\RuntimeInsight\DTO\LogEntry;
use ClarityPHP\RuntimeInsight\DTO\TimelineEvent;
use ClarityPHP\RuntimeInsight\DTO\TimelineResult;

use function array_slice;
use function count;
use function strtotime;

/**
 * Builds a runtime timeline from log entries (relative T+ seconds from first event).
 * Adds synthetic request_started and request_ended for context.
 */
final class TimelineService
{
    /**
     * @param int $lastN Maximum number of log entries to include (most recent)
     */
    public function __construct(
        private readonly LogParserInterface $parser,
        private readonly int $lastN = 20,
    ) {}

    /**
     * Build timeline from the last N error entries in the log file.
     * Events get relative T+ seconds; request_started and request_ended are added.
     */
    public function buildFromLog(string $logPath): TimelineResult
    {
        $entries = $this->parser->parseFile($logPath);
        if ($entries === []) {
            return new TimelineResult(logPath: $logPath);
        }

        $last = array_slice($entries, -$this->lastN);
        $events = $this->entriesToEvents($last);
        if ($events !== []) {
            $requestStart = new TimelineEvent(0.0, 'request_started', 'Request started', '');
            $events = [$requestStart, ...$events];
        }

        return new TimelineResult(logPath: $logPath, events: $events);
    }

    /**
     * @param array<int, LogEntry> $entries
     *
     * @return array<int, TimelineEvent>
     */
    private function entriesToEvents(array $entries): array
    {
        $firstTs = null;
        $withTs = [];

        foreach ($entries as $entry) {
            $ts = $entry->timestamp !== null && $entry->timestamp !== ''
                ? strtotime($entry->timestamp)
                : null;
            if ($ts === false) {
                $ts = null;
            }
            if ($firstTs === null && $ts !== null) {
                $firstTs = $ts;
            }
            $withTs[] = ['entry' => $entry, 'ts' => $ts];
        }

        if ($firstTs === null) {
            $firstTs = 0;
        }

        $events = [];
        foreach ($withTs as $i => $item) {
            $entry = $item['entry'];
            $ts = $item['ts'];
            $relative = $ts !== null ? $ts - $firstTs : (float) $i;
            $detail = $entry->file !== 'unknown' ? $entry->file . ':' . $entry->line : $entry->message;
            $events[] = new TimelineEvent(
                relativeSeconds: (float) $relative,
                type: 'exception',
                label: $entry->exceptionClass,
                detail: $entry->message . ' at ' . $detail,
            );
        }

        if ($events !== []) {
            $lastRel = $events[count($events) - 1]->relativeSeconds;
            $events[] = new TimelineEvent(
                relativeSeconds: $lastRel + 0.01,
                type: 'request_ended',
                label: 'Request ended',
                detail: 'HTTP 500',
            );
        }

        return $events;
    }
}
