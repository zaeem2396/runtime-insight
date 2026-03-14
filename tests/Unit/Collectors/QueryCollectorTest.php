<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Collectors;

use ClarityPHP\RuntimeInsight\Collectors\QueryCollector;
use ClarityPHP\RuntimeInsight\DTO\DatabaseContext;
use ClarityPHP\RuntimeInsight\DTO\ExceptionInfo;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use ClarityPHP\RuntimeInsight\DTO\SourceContext;
use ClarityPHP\RuntimeInsight\DTO\StackTraceInfo;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class QueryCollectorTest extends TestCase
{
    #[Test]
    public function it_returns_null_when_no_database_context(): void
    {
        $context = new RuntimeContext(
            exception: new ExceptionInfo('E', 'msg', 0, '/f', 1),
            stackTrace: new StackTraceInfo(frames: []),
            sourceContext: SourceContext::empty(),
        );
        $collector = new QueryCollector();

        $this->assertNull($collector->collect($context));
    }

    #[Test]
    public function it_returns_query_signal_when_database_context_present(): void
    {
        $db = new DatabaseContext(recentQueries: ['SELECT 1', 'SELECT 2']);
        $context = new RuntimeContext(
            exception: new ExceptionInfo('E', 'msg', 0, '/f', 1),
            stackTrace: new StackTraceInfo(frames: []),
            sourceContext: SourceContext::empty(),
            databaseContext: $db,
        );
        $collector = new QueryCollector();

        $payload = $collector->collect($context);

        $this->assertIsArray($payload);
        $this->assertSame(2, $payload['count']);
        $this->assertSame(['SELECT 1', 'SELECT 2'], $payload['recent_queries']);
    }

    #[Test]
    public function get_name_returns_query(): void
    {
        $this->assertSame('query', (new QueryCollector())->getName());
    }
}
