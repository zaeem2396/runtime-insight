<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Collectors;

use ClarityPHP\RuntimeInsight\Collectors\RequestCollector;
use ClarityPHP\RuntimeInsight\DTO\ExceptionInfo;
use ClarityPHP\RuntimeInsight\DTO\RequestContext;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use ClarityPHP\RuntimeInsight\DTO\SourceContext;
use ClarityPHP\RuntimeInsight\DTO\StackTraceInfo;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RequestCollectorTest extends TestCase
{
    #[Test]
    public function it_returns_null_when_no_request_context(): void
    {
        $context = new RuntimeContext(
            exception: new ExceptionInfo('E', 'msg', 0, '/f', 1),
            stackTrace: new StackTraceInfo(frames: []),
            sourceContext: SourceContext::empty(),
        );
        $collector = new RequestCollector();

        $this->assertNull($collector->collect($context));
    }

    #[Test]
    public function it_returns_request_signal_with_route(): void
    {
        $request = new RequestContext(
            method: 'POST',
            uri: 'https://example.com/orders',
            route: 'orders.store',
        );
        $context = new RuntimeContext(
            exception: new ExceptionInfo('E', 'msg', 0, '/f', 1),
            stackTrace: new StackTraceInfo(frames: []),
            sourceContext: SourceContext::empty(),
            requestContext: $request,
        );
        $collector = new RequestCollector();

        $payload = $collector->collect($context);

        $this->assertSame('POST', $payload['method']);
        $this->assertSame('https://example.com/orders', $payload['uri']);
        $this->assertSame('orders.store', $payload['route']);
    }

    #[Test]
    public function get_name_returns_request(): void
    {
        $this->assertSame('request', (new RequestCollector())->getName());
    }
}
