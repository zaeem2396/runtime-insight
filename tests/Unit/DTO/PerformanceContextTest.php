<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\DTO;

use ClarityPHP\RuntimeInsight\DTO\PerformanceContext;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PerformanceContextTest extends TestCase
{
    #[Test]
    public function it_creates_empty_context(): void
    {
        $context = new PerformanceContext();

        $this->assertTrue($context->isEmpty());
        $this->assertSame(0, $context->peakMemoryBytes);
        $this->assertSame(0.0, $context->scriptRuntimeSeconds);
        $this->assertSame('0 B', $context->getPeakMemoryFormatted());
    }

    #[Test]
    public function it_formats_peak_memory(): void
    {
        $context = new PerformanceContext(peakMemoryBytes: 1024);

        $this->assertSame('1 KB', $context->getPeakMemoryFormatted());
        $this->assertFalse($context->isEmpty());
    }

    #[Test]
    public function it_formats_large_memory(): void
    {
        $context = new PerformanceContext(peakMemoryBytes: 1024 * 1024 * 5);

        $this->assertSame('5 MB', $context->getPeakMemoryFormatted());
    }

    #[Test]
    public function it_converts_to_array(): void
    {
        $context = new PerformanceContext(
            peakMemoryBytes: 2048,
            scriptRuntimeSeconds: 1.5,
        );

        $array = $context->toArray();

        $this->assertSame(2048, $array['peak_memory_bytes']);
        $this->assertSame('2 KB', $array['peak_memory_formatted']);
        $this->assertSame(1.5, $array['script_runtime_seconds']);
    }

    #[Test]
    public function it_is_not_empty_when_script_runtime_set(): void
    {
        $context = new PerformanceContext(peakMemoryBytes: 0, scriptRuntimeSeconds: 0.1);

        $this->assertFalse($context->isEmpty());
    }
}
