<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Engine;

use ClarityPHP\RuntimeInsight\Config;
use ClarityPHP\RuntimeInsight\Contracts\ExplanationCacheInterface;
use ClarityPHP\RuntimeInsight\Contracts\ExplanationEngineInterface;
use ClarityPHP\RuntimeInsight\DTO\ExceptionInfo;
use ClarityPHP\RuntimeInsight\DTO\Explanation;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use ClarityPHP\RuntimeInsight\DTO\SourceContext;
use ClarityPHP\RuntimeInsight\DTO\StackTraceInfo;
use ClarityPHP\RuntimeInsight\Engine\ArrayExplanationCache;
use ClarityPHP\RuntimeInsight\Engine\CachingExplanationEngine;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CachingExplanationEngineTest extends TestCase
{
    #[Test]
    public function it_delegates_when_cache_disabled(): void
    {
        $explanation = new Explanation(message: 'From delegate', cause: 'Cause', suggestions: [], confidence: 0.8);
        $delegate = $this->createMock(ExplanationEngineInterface::class);
        $delegate->expects($this->once())
            ->method('explain')
            ->willReturn($explanation);

        $config = new Config(cacheEnabled: false);
        $engine = new CachingExplanationEngine($delegate, new ArrayExplanationCache(), $config);
        $context = $this->createContext('Error');

        $result = $engine->explain($context);

        $this->assertSame($explanation, $result);
    }

    #[Test]
    public function it_caches_result_when_cache_enabled(): void
    {
        $explanation = new Explanation(message: 'Cached', cause: 'Cause', suggestions: [], confidence: 0.9);
        $delegate = $this->createMock(ExplanationEngineInterface::class);
        $delegate->expects($this->once())
            ->method('explain')
            ->willReturn($explanation);

        $config = new Config(cacheEnabled: true, cacheTtl: 3600);
        $cache = new ArrayExplanationCache();
        $engine = new CachingExplanationEngine($delegate, $cache, $config);
        $context = $this->createContext('Same error', 'Exception', '/same.php', 42);

        $first = $engine->explain($context);
        $second = $engine->explain($context);

        $this->assertSame('Cached', $first->getMessage());
        $this->assertSame($first, $second);
    }

    #[Test]
    public function it_misses_cache_for_different_error_signature(): void
    {
        $explanation1 = new Explanation(message: 'First', cause: '', suggestions: [], confidence: 0.5);
        $explanation2 = new Explanation(message: 'Second', cause: '', suggestions: [], confidence: 0.6);
        $delegate = $this->createMock(ExplanationEngineInterface::class);
        $delegate->expects($this->exactly(2))
            ->method('explain')
            ->willReturnOnConsecutiveCalls($explanation1, $explanation2);

        $config = new Config(cacheEnabled: true, cacheTtl: 3600);
        $engine = new CachingExplanationEngine($delegate, new ArrayExplanationCache(), $config);

        $context1 = $this->createContext('Error A', 'Exception', '/a.php', 1);
        $context2 = $this->createContext('Error B', 'Exception', '/b.php', 2);

        $this->assertSame('First', $engine->explain($context1)->getMessage());
        $this->assertSame('Second', $engine->explain($context2)->getMessage());
    }

    #[Test]
    public function it_returns_cached_explanation_on_hit(): void
    {
        $stored = new Explanation(message: 'Stored', cause: 'Stored cause', suggestions: [], confidence: 1.0);
        $cache = $this->createMock(ExplanationCacheInterface::class);
        $cache->method('get')->willReturn($stored);
        $cache->expects($this->once())->method('get');
        $cache->expects($this->never())->method('set');

        $delegate = $this->createMock(ExplanationEngineInterface::class);
        $delegate->expects($this->never())->method('explain');

        $config = new Config(cacheEnabled: true);
        $engine = new CachingExplanationEngine($delegate, $cache, $config);
        $context = $this->createContext('Any');

        $result = $engine->explain($context);

        $this->assertSame($stored, $result);
        $this->assertSame('Stored', $result->getMessage());
    }

    private function createContext(string $message, string $class = 'Exception', string $file = '/test/file.php', int $line = 10): RuntimeContext
    {
        return new RuntimeContext(
            exception: new ExceptionInfo(
                class: $class,
                message: $message,
                code: 0,
                file: $file,
                line: $line,
            ),
            stackTrace: new StackTraceInfo(frames: []),
            sourceContext: SourceContext::empty(),
        );
    }
}
