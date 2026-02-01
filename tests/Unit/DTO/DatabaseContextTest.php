<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\DTO;

use ClarityPHP\RuntimeInsight\DTO\DatabaseContext;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DatabaseContextTest extends TestCase
{
    #[Test]
    public function it_creates_empty_context(): void
    {
        $context = new DatabaseContext();

        $this->assertTrue($context->isEmpty());
        $this->assertSame([], $context->recentQueries);
        $this->assertSame(['recent_queries' => []], $context->toArray());
    }

    #[Test]
    public function it_stores_recent_queries(): void
    {
        $queries = [
            'SELECT * FROM users WHERE id = 1',
            'UPDATE orders SET status = ? [1.23ms]',
        ];
        $context = new DatabaseContext(recentQueries: $queries);

        $this->assertFalse($context->isEmpty());
        $this->assertSame($queries, $context->recentQueries);
        $this->assertSame(['recent_queries' => $queries], $context->toArray());
    }
}
