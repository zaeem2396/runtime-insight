<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\DTO;

use ClarityPHP\RuntimeInsight\DTO\RequestContext;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RequestContextTest extends TestCase
{
    #[Test]
    public function to_array_includes_route_when_set(): void
    {
        $ctx = new RequestContext(
            method: 'GET',
            uri: 'https://example.com/orders',
            route: 'orders.index',
        );
        $arr = $ctx->toArray();
        $this->assertSame('orders.index', $arr['route']);
    }

    #[Test]
    public function to_array_includes_all_core_fields(): void
    {
        $ctx = new RequestContext(method: 'POST', uri: '/api/orders', body: ['key' => 'value']);
        $arr = $ctx->toArray();
        $this->assertSame('POST', $arr['method']);
        $this->assertSame('/api/orders', $arr['uri']);
        $this->assertSame(['key' => 'value'], $arr['body']);
    }
}
