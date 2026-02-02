<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\DTO;

use ClarityPHP\RuntimeInsight\DTO\DatabaseContext;
use ClarityPHP\RuntimeInsight\DTO\ExceptionInfo;
use ClarityPHP\RuntimeInsight\DTO\PerformanceContext;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use ClarityPHP\RuntimeInsight\DTO\SourceContext;
use ClarityPHP\RuntimeInsight\DTO\StackTraceInfo;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RuntimeContextTest extends TestCase
{
    #[Test]
    public function to_summary_includes_recent_queries_when_database_context_present(): void
    {
        $context = new RuntimeContext(
            exception: new ExceptionInfo('Exception', 'Error', 0, '/file.php', 10),
            stackTrace: new StackTraceInfo(frames: []),
            sourceContext: SourceContext::empty(),
            databaseContext: new DatabaseContext(recentQueries: [
                'SELECT * FROM users',
                'UPDATE orders SET status = 1 [2.50ms]',
            ]),
        );

        $summary = $context->toSummary();

        $this->assertStringContainsString('Recent queries:', $summary);
        $this->assertStringContainsString('SELECT * FROM users', $summary);
        $this->assertStringContainsString('UPDATE orders SET status = 1 [2.50ms]', $summary);
    }

    #[Test]
    public function to_array_includes_database_context_key(): void
    {
        $context = new RuntimeContext(
            exception: new ExceptionInfo('Exception', 'Error', 0, '/file.php', 10),
            stackTrace: new StackTraceInfo(frames: []),
            sourceContext: SourceContext::empty(),
            databaseContext: new DatabaseContext(recentQueries: ['SELECT 1']),
        );

        $array = $context->toArray();

        $this->assertArrayHasKey('database_context', $array);
        $this->assertSame(['recent_queries' => ['SELECT 1']], $array['database_context']);
    }

    #[Test]
    public function to_summary_includes_performance_when_present(): void
    {
        $context = new RuntimeContext(
            exception: new ExceptionInfo('Exception', 'Error', 0, '/file.php', 10),
            stackTrace: new StackTraceInfo(frames: []),
            sourceContext: SourceContext::empty(),
            performanceContext: new PerformanceContext(peakMemoryBytes: 1024 * 1024, scriptRuntimeSeconds: 0),
        );

        $summary = $context->toSummary();

        $this->assertStringContainsString('Performance:', $summary);
        $this->assertStringContainsString('Peak memory:', $summary);
        $this->assertStringContainsString('1 MB', $summary);
    }

    #[Test]
    public function to_array_includes_performance_context_key(): void
    {
        $context = new RuntimeContext(
            exception: new ExceptionInfo('Exception', 'Error', 0, '/file.php', 10),
            stackTrace: new StackTraceInfo(frames: []),
            sourceContext: SourceContext::empty(),
            performanceContext: new PerformanceContext(peakMemoryBytes: 512, scriptRuntimeSeconds: 0.5),
        );

        $array = $context->toArray();

        $this->assertArrayHasKey('performance_context', $array);
        $this->assertSame(512, $array['performance_context']['peak_memory_bytes']);
        $this->assertSame(0.5, $array['performance_context']['script_runtime_seconds']);
    }
}
