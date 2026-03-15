<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Event;

use ClarityPHP\RuntimeInsight\Event\BeforeAnalysisEvent;
use ClarityPHP\RuntimeInsight\Event\InMemoryEventDispatcher;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InMemoryEventDispatcherTest extends TestCase
{
    #[Test]
    public function dispatch_invokes_listeners_for_event_class(): void
    {
        $dispatcher = new InMemoryEventDispatcher();
        $called = false;
        $dispatcher->addListener(BeforeAnalysisEvent::class, function (BeforeAnalysisEvent $e) use (&$called): void {
            $called = true;
        });

        $context = $this->createContext();
        $dispatcher->dispatch(new BeforeAnalysisEvent($context));

        $this->assertTrue($called);
    }

    #[Test]
    public function add_listener_returns_self(): void
    {
        $dispatcher = new InMemoryEventDispatcher();
        $this->assertSame($dispatcher, $dispatcher->addListener(BeforeAnalysisEvent::class, static function (): void {}));
    }

    #[Test]
    public function dispatch_does_nothing_when_no_listeners(): void
    {
        $dispatcher = new InMemoryEventDispatcher();
        $dispatcher->dispatch(new BeforeAnalysisEvent($this->createContext()));
        $this->expectNotToPerformAssertions();
    }

    private function createContext(): \ClarityPHP\RuntimeInsight\DTO\RuntimeContext
    {
        return new \ClarityPHP\RuntimeInsight\DTO\RuntimeContext(
            exception: new \ClarityPHP\RuntimeInsight\DTO\ExceptionInfo('E', 'msg', 0, '/f', 1),
            stackTrace: new \ClarityPHP\RuntimeInsight\DTO\StackTraceInfo([]),
            sourceContext: \ClarityPHP\RuntimeInsight\DTO\SourceContext::empty(),
        );
    }
}
