<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\AI;

use ClarityPHP\RuntimeInsight\AI\FallbackChainProvider;
use ClarityPHP\RuntimeInsight\Contracts\AIProviderInterface;
use ClarityPHP\RuntimeInsight\DTO\ExceptionInfo;
use ClarityPHP\RuntimeInsight\DTO\Explanation;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use ClarityPHP\RuntimeInsight\DTO\SourceContext;
use ClarityPHP\RuntimeInsight\DTO\StackTraceInfo;
use Exception;
use PHPUnit\Framework\TestCase;

final class FallbackChainProviderTest extends TestCase
{
    public function test_returns_first_non_empty_explanation(): void
    {
        $context = $this->createContext();

        $empty = $this->createMock(AIProviderInterface::class);
        $empty->method('isAvailable')->willReturn(true);
        $empty->method('analyze')->with($context)->willReturn(Explanation::empty());

        $withResult = $this->createMock(AIProviderInterface::class);
        $withResult->method('isAvailable')->willReturn(true);
        $withResult->method('analyze')->with($context)->willReturn(
            new Explanation('Got it', 'Cause', ['Fix'], 0.9),
        );

        $chain = new FallbackChainProvider([$empty, $withResult]);

        $result = $chain->analyze($context);

        $this->assertFalse($result->isEmpty());
        $this->assertSame('Got it', $result->getMessage());
    }

    public function test_returns_empty_when_all_providers_return_empty(): void
    {
        $context = $this->createContext();

        $p1 = $this->createMock(AIProviderInterface::class);
        $p1->method('isAvailable')->willReturn(true);
        $p1->method('analyze')->willReturn(Explanation::empty());

        $p2 = $this->createMock(AIProviderInterface::class);
        $p2->method('isAvailable')->willReturn(true);
        $p2->method('analyze')->willReturn(Explanation::empty());

        $chain = new FallbackChainProvider([$p1, $p2]);

        $result = $chain->analyze($context);

        $this->assertTrue($result->isEmpty());
    }

    public function test_skips_unavailable_providers(): void
    {
        $context = $this->createContext();

        $unavailable = $this->createMock(AIProviderInterface::class);
        $unavailable->method('isAvailable')->willReturn(false);
        $unavailable->expects($this->never())->method('analyze');

        $available = $this->createMock(AIProviderInterface::class);
        $available->method('isAvailable')->willReturn(true);
        $available->method('analyze')->with($context)->willReturn(
            new Explanation('From second', 'Cause', [], 0.8),
        );

        $chain = new FallbackChainProvider([$unavailable, $available]);

        $result = $chain->analyze($context);

        $this->assertSame('From second', $result->getMessage());
    }

    public function test_is_available_when_any_provider_available(): void
    {
        $unavailable = $this->createMock(AIProviderInterface::class);
        $unavailable->method('isAvailable')->willReturn(false);

        $available = $this->createMock(AIProviderInterface::class);
        $available->method('isAvailable')->willReturn(true);

        $chain = new FallbackChainProvider([$unavailable, $available]);

        $this->assertTrue($chain->isAvailable());
    }

    public function test_is_not_available_when_no_provider_available(): void
    {
        $p1 = $this->createMock(AIProviderInterface::class);
        $p1->method('isAvailable')->willReturn(false);

        $p2 = $this->createMock(AIProviderInterface::class);
        $p2->method('isAvailable')->willReturn(false);

        $chain = new FallbackChainProvider([$p1, $p2]);

        $this->assertFalse($chain->isAvailable());
    }

    public function test_get_name_returns_fallback(): void
    {
        $chain = new FallbackChainProvider([]);

        $this->assertSame('fallback', $chain->getName());
    }

    public function test_returns_first_result_immediately(): void
    {
        $context = $this->createContext();

        $first = $this->createMock(AIProviderInterface::class);
        $first->method('isAvailable')->willReturn(true);
        $first->method('analyze')->with($context)->willReturn(
            new Explanation('First', 'Cause', [], 0.9),
        );

        $second = $this->createMock(AIProviderInterface::class);
        $second->method('isAvailable')->willReturn(true);
        $second->expects($this->never())->method('analyze');

        $chain = new FallbackChainProvider([$first, $second]);

        $result = $chain->analyze($context);

        $this->assertSame('First', $result->getMessage());
    }

    private function createContext(): RuntimeContext
    {
        $exception = new Exception('Test error');
        $exceptionInfo = ExceptionInfo::fromThrowable($exception);

        return new RuntimeContext(
            exception: $exceptionInfo,
            stackTrace: new StackTraceInfo([]),
            sourceContext: SourceContext::empty(),
        );
    }
}
