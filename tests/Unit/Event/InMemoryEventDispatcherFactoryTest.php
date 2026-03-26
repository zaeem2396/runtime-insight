<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Event;

use ClarityPHP\RuntimeInsight\Config;
use ClarityPHP\RuntimeInsight\DTO\ExceptionInfo;
use ClarityPHP\RuntimeInsight\DTO\Explanation;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use ClarityPHP\RuntimeInsight\DTO\SourceContext;
use ClarityPHP\RuntimeInsight\DTO\StackTraceInfo;
use ClarityPHP\RuntimeInsight\Event\AfterAnalysisEvent;
use ClarityPHP\RuntimeInsight\Event\InMemoryEventDispatcher;
use ClarityPHP\RuntimeInsight\Event\InMemoryEventDispatcherFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InMemoryEventDispatcherFactoryTest extends TestCase
{
    #[Test]
    public function create_returns_dispatcher_that_handles_after_analysis_when_webhooks_on(): void
    {
        $config = Config::fromArray([
            'enabled' => true,
            'ai' => ['enabled' => false],
            'webhooks' => [
                'enabled' => true,
                'urls' => ['http://127.0.0.1:9/discard'],
                'timeout' => 1,
            ],
        ]);
        $dispatcher = InMemoryEventDispatcherFactory::create($config, null);
        $this->assertInstanceOf(InMemoryEventDispatcher::class, $dispatcher);
        $this->assertTrue($config->getWebhookSettings()->shouldDeliver());
        $context = new RuntimeContext(
            exception: new ExceptionInfo('E', 'm', 0, '/f', 1),
            stackTrace: new StackTraceInfo([]),
            sourceContext: SourceContext::empty(),
        );
        $dispatcher->dispatch(new AfterAnalysisEvent(new Explanation('a', 'b', [], 0.1), $context));
    }

    #[Test]
    public function create_without_webhooks_returns_plain_dispatcher(): void
    {
        $config = Config::fromArray(['enabled' => true, 'ai' => ['enabled' => false]]);
        $dispatcher = InMemoryEventDispatcherFactory::create($config, null);
        $this->assertInstanceOf(InMemoryEventDispatcher::class, $dispatcher);
        $this->assertFalse($config->getWebhookSettings()->shouldDeliver());
    }
}
