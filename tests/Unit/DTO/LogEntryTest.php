<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\DTO;

use ClarityPHP\RuntimeInsight\DTO\LogEntry;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LogEntryTest extends TestCase
{
    #[Test]
    public function to_array_contains_all_fields(): void
    {
        $entry = new LogEntry(
            message: 'Test message',
            file: '/app/Test.php',
            line: 42,
            exceptionClass: 'TypeError',
            timestamp: '2026-02-01 12:00:00',
        );

        $arr = $entry->toArray();

        $this->assertSame('Test message', $arr['message']);
        $this->assertSame('/app/Test.php', $arr['file']);
        $this->assertSame(42, $arr['line']);
        $this->assertSame('TypeError', $arr['exception_class']);
        $this->assertSame('2026-02-01 12:00:00', $arr['timestamp']);
    }
}
