<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Collectors;

use ClarityPHP\RuntimeInsight\Collectors\CollectorRegistry;
use ClarityPHP\RuntimeInsight\Contracts\SignalCollectorInterface;
use ClarityPHP\RuntimeInsight\DTO\CollectorsContext;
use ClarityPHP\RuntimeInsight\DTO\ExceptionInfo;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use ClarityPHP\RuntimeInsight\DTO\SourceContext;
use ClarityPHP\RuntimeInsight\DTO\StackTraceInfo;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CollectorRegistryTest extends TestCase
{
    #[Test]
    public function enrich_returns_same_context_when_no_collectors(): void
    {
        $registry = new CollectorRegistry();
        $context = $this->createContext();

        $result = $registry->enrich($context);

        $this->assertSame($context, $result);
        $this->assertNull($result->collectorsContext);
    }

    #[Test]
    public function enrich_returns_same_context_when_collector_returns_null(): void
    {
        $collector = $this->createMock(SignalCollectorInterface::class);
        $collector->method('getName')->willReturn('test');
        $collector->method('isEnabled')->willReturn(true);
        $collector->method('collect')->willReturn(null);

        $registry = new CollectorRegistry();
        $registry->addCollector($collector);
        $context = $this->createContext();

        $result = $registry->enrich($context);

        $this->assertSame($context, $result);
    }

    #[Test]
    public function enrich_returns_new_context_with_collectors_context_when_collector_returns_payload(): void
    {
        $collector = $this->createMock(SignalCollectorInterface::class);
        $collector->method('getName')->willReturn('test');
        $collector->method('isEnabled')->willReturn(true);
        $collector->method('collect')->willReturn(['key' => 'value']);

        $registry = new CollectorRegistry();
        $registry->addCollector($collector);
        $context = $this->createContext();

        $result = $registry->enrich($context);

        $this->assertNotSame($context, $result);
        $this->assertInstanceOf(CollectorsContext::class, $result->collectorsContext);
        $this->assertSame(['test' => ['key' => 'value']], $result->collectorsContext->signals);
    }

    #[Test]
    public function add_collector_returns_self(): void
    {
        $collector = $this->createMock(SignalCollectorInterface::class);
        $registry = new CollectorRegistry();

        $this->assertSame($registry, $registry->addCollector($collector));
    }

    #[Test]
    public function get_collectors_returns_added_collectors(): void
    {
        $collector = $this->createMock(SignalCollectorInterface::class);
        $registry = new CollectorRegistry();
        $registry->addCollector($collector);

        $this->assertCount(1, $registry->getCollectors());
        $this->assertSame($collector, $registry->getCollectors()[0]);
    }

    #[Test]
    public function enrich_skips_disabled_collectors(): void
    {
        $collector = $this->createMock(SignalCollectorInterface::class);
        $collector->method('getName')->willReturn('disabled');
        $collector->method('isEnabled')->willReturn(false);
        $collector->method('collect')->willReturn(['data' => true]);

        $registry = new CollectorRegistry();
        $registry->addCollector($collector);
        $context = $this->createContext();

        $result = $registry->enrich($context);

        $this->assertSame($context, $result);
    }

    private function createContext(): RuntimeContext
    {
        return new RuntimeContext(
            exception: new ExceptionInfo(
                class: 'Exception',
                message: 'Test',
                code: 0,
                file: '/test.php',
                line: 1,
            ),
            stackTrace: new StackTraceInfo(frames: []),
            sourceContext: SourceContext::empty(),
        );
    }
}
