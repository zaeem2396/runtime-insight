<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Log;

use ClarityPHP\RuntimeInsight\Contracts\LogParserInterface;
use ClarityPHP\RuntimeInsight\DTO\LogEntry;
use ClarityPHP\RuntimeInsight\Log\TimelineService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function count;

final class TimelineServiceTest extends TestCase
{
    #[Test]
    public function build_from_log_returns_empty_when_parser_returns_no_entries(): void
    {
        $parser = $this->createMock(LogParserInterface::class);
        $parser->method('parseFile')->willReturn([]);
        $service = new TimelineService($parser, 10);

        $result = $service->buildFromLog('/any.log');

        $this->assertTrue($result->isEmpty());
        $this->assertSame('/any.log', $result->logPath);
    }

    #[Test]
    public function build_from_log_returns_events_with_request_started_and_ended(): void
    {
        $entries = [
            new LogEntry('Error one', '/app/A.php', 10, 'Exception', '2026-02-01 12:00:00'),
            new LogEntry('Error two', '/app/B.php', 20, 'TypeError', '2026-02-01 12:00:01'),
        ];
        $parser = $this->createMock(LogParserInterface::class);
        $parser->method('parseFile')->willReturn($entries);
        $service = new TimelineService($parser, 10);

        $result = $service->buildFromLog('/var/log/app.log');

        $this->assertFalse($result->isEmpty());
        $this->assertGreaterThanOrEqual(3, count($result->events));
        $this->assertSame('request_started', $result->events[0]->type);
        $this->assertSame('exception', $result->events[1]->type);
        $last = $result->events[count($result->events) - 1];
        $this->assertSame('request_ended', $last->type);
    }
}
