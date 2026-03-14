<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Collectors;

use ClarityPHP\RuntimeInsight\Collectors\MemoryCollector;
use ClarityPHP\RuntimeInsight\DTO\ExceptionInfo;
use ClarityPHP\RuntimeInsight\DTO\PerformanceContext;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use ClarityPHP\RuntimeInsight\DTO\SourceContext;
use ClarityPHP\RuntimeInsight\DTO\StackTraceInfo;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MemoryCollectorTest extends TestCase
{
    #[Test]
    public function it_returns_null_when_no_performance_context(): void
    {
        $context = new RuntimeContext(
            exception: new ExceptionInfo('E', 'msg', 0, '/f', 1),
            stackTrace: new StackTraceInfo(frames: []),
            sourceContext: SourceContext::empty(),
        );
        $collector = new MemoryCollector();

        $this->assertNull($collector->collect($context));
    }

    #[Test]
    public function it_returns_memory_signal_when_performance_context_present(): void
    {
        $perf = new PerformanceContext(peakMemoryBytes: 64 * 1024 * 1024);
        $context = new RuntimeContext(
            exception: new ExceptionInfo('E', 'msg', 0, '/f', 1),
            stackTrace: new StackTraceInfo(frames: []),
            sourceContext: SourceContext::empty(),
            performanceContext: $perf,
        );
        $collector = new MemoryCollector();

        $payload = $collector->collect($context);

        $this->assertIsArray($payload);
        $this->assertSame(64 * 1024 * 1024, $payload['peak_memory_bytes']);
    }

    #[Test]
    public function get_name_returns_memory(): void
    {
        $this->assertSame('memory', (new MemoryCollector())->getName());
    }
}
