<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Log;

use ClarityPHP\RuntimeInsight\DTO\LogEntry;
use ClarityPHP\RuntimeInsight\Log\LaravelLogParser;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function in_array;

final class LaravelLogParserTest extends TestCase
{
    private LaravelLogParser $parser;

    protected function setUp(): void
    {
        $this->parser = new LaravelLogParser();
    }

    #[Test]
    public function parse_file_returns_empty_array_for_missing_file(): void
    {
        $entries = $this->parser->parseFile('/nonexistent/path.log');

        $this->assertSame([], $entries);
    }

    #[Test]
    public function parse_file_extracts_entries_from_laravel_format(): void
    {
        $log = sys_get_temp_dir() . '/runtime-insight-test-' . uniqid() . '.log';
        $content = "[2026-02-01 12:00:00] local.ERROR: TypeError (Argument #1 must be of type string, null given)\n at /app/Http/Controllers/OrderController.php:42\n";
        file_put_contents($log, $content);

        try {
            $entries = $this->parser->parseFile($log);
            $this->assertCount(1, $entries);
            $entry = $entries[0];
            $this->assertInstanceOf(LogEntry::class, $entry);
            $this->assertStringContainsString('Argument #1', $entry->message);
            $this->assertSame('/app/Http/Controllers/OrderController.php', $entry->file);
            $this->assertSame(42, $entry->line);
            $this->assertTrue(in_array($entry->exceptionClass, ['TypeError', 'Exception'], true));
            $this->assertSame('2026-02-01 12:00:00', $entry->timestamp);
        } finally {
            @unlink($log);
        }
    }
}
