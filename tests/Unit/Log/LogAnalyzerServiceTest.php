<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Log;

use ClarityPHP\RuntimeInsight\Contracts\LogParserInterface;
use ClarityPHP\RuntimeInsight\DTO\LogEntry;
use ClarityPHP\RuntimeInsight\Log\LogAnalyzerService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LogAnalyzerServiceTest extends TestCase
{
    #[Test]
    public function analyze_returns_empty_result_when_parser_returns_no_entries(): void
    {
        $parser = $this->createMock(LogParserInterface::class);
        $parser->method('parseFile')->willReturn([]);
        $service = new LogAnalyzerService($parser, 10);

        $result = $service->analyze('/any/path.log');

        $this->assertTrue($result->isEmpty());
        $this->assertSame(0, $result->totalErrors);
    }

    #[Test]
    public function analyze_groups_by_signature_and_fills_top_failures(): void
    {
        $entry = new LogEntry('Undefined array key "id"', '/app/Test.php', 10, 'ErrorException', null);
        $parser = $this->createMock(LogParserInterface::class);
        $parser->method('parseFile')->willReturn([$entry, $entry, $entry]);
        $service = new LogAnalyzerService($parser, 5);

        $result = $service->analyze('/var/log/app.log');

        $this->assertFalse($result->isEmpty());
        $this->assertSame(3, $result->totalErrors);
        $this->assertCount(1, $result->topFailures);
        $this->assertSame(3, $result->topFailures[0]['count']);
    }
}
