<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Engine;

use ClarityPHP\RuntimeInsight\Config;
use ClarityPHP\RuntimeInsight\Contracts\AIProviderInterface;
use ClarityPHP\RuntimeInsight\Contracts\ExplanationStrategyInterface;
use ClarityPHP\RuntimeInsight\DTO\ExceptionInfo;
use ClarityPHP\RuntimeInsight\DTO\Explanation;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use ClarityPHP\RuntimeInsight\DTO\SourceContext;
use ClarityPHP\RuntimeInsight\DTO\StackTraceInfo;
use ClarityPHP\RuntimeInsight\Engine\ExplanationEngine;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ExplanationEngineTest extends TestCase
{
    private ExplanationEngine $engine;

    protected function setUp(): void
    {
        $this->engine = new ExplanationEngine(new Config());
    }

    #[Test]
    public function it_returns_fallback_explanation_when_no_strategies_match(): void
    {
        $context = $this->createContext('Some error', 'Exception');

        $explanation = $this->engine->explain($context);

        $this->assertInstanceOf(Explanation::class, $explanation);
        $this->assertSame('Some error', $explanation->getMessage());
        $this->assertSame(0.3, $explanation->getConfidence());
    }

    #[Test]
    public function it_uses_matching_strategy(): void
    {
        $mockStrategy = $this->createMock(ExplanationStrategyInterface::class);
        $mockStrategy->method('supports')->willReturn(true);
        $mockStrategy->method('priority')->willReturn(100);
        $mockStrategy->method('explain')->willReturn(
            new Explanation(
                message: 'Strategy explanation',
                cause: 'Strategy cause',
                suggestions: ['Strategy suggestion'],
                confidence: 0.95,
            ),
        );

        $this->engine->addStrategy($mockStrategy);

        $context = $this->createContext('Test error', 'Exception');
        $explanation = $this->engine->explain($context);

        $this->assertSame('Strategy explanation', $explanation->getMessage());
        $this->assertSame(0.95, $explanation->getConfidence());
    }

    #[Test]
    public function it_skips_non_matching_strategies(): void
    {
        $nonMatchingStrategy = $this->createMock(ExplanationStrategyInterface::class);
        $nonMatchingStrategy->method('supports')->willReturn(false);
        $nonMatchingStrategy->method('priority')->willReturn(100);

        $this->engine->addStrategy($nonMatchingStrategy);

        $context = $this->createContext('Test error', 'Exception');
        $explanation = $this->engine->explain($context);

        // Should fall back to default explanation
        $this->assertSame(0.3, $explanation->getConfidence());
    }

    #[Test]
    public function it_sorts_strategies_by_priority(): void
    {
        $lowPriorityStrategy = $this->createMock(ExplanationStrategyInterface::class);
        $lowPriorityStrategy->method('priority')->willReturn(10);
        $lowPriorityStrategy->method('supports')->willReturn(true);
        $lowPriorityStrategy->method('explain')->willReturn(
            new Explanation(message: 'Low', cause: '', suggestions: [], confidence: 0.5),
        );

        $highPriorityStrategy = $this->createMock(ExplanationStrategyInterface::class);
        $highPriorityStrategy->method('priority')->willReturn(100);
        $highPriorityStrategy->method('supports')->willReturn(true);
        $highPriorityStrategy->method('explain')->willReturn(
            new Explanation(message: 'High', cause: '', suggestions: [], confidence: 0.9),
        );

        // Add low priority first
        $this->engine->addStrategy($lowPriorityStrategy);
        $this->engine->addStrategy($highPriorityStrategy);

        $context = $this->createContext('Test', 'Exception');
        $explanation = $this->engine->explain($context);

        // High priority should be used first
        $this->assertSame('High', $explanation->getMessage());
    }

    #[Test]
    public function it_returns_registered_strategies(): void
    {
        $strategy = $this->createMock(ExplanationStrategyInterface::class);
        $strategy->method('priority')->willReturn(50);

        $this->engine->addStrategy($strategy);

        $strategies = $this->engine->getStrategies();

        $this->assertCount(1, $strategies);
        $this->assertSame($strategy, $strategies[0]);
    }

    #[Test]
    public function it_falls_back_to_rule_based_when_ai_returns_empty(): void
    {
        $config = Config::fromArray([
            'enabled' => true,
            'ai' => [
                'enabled' => true,
                'provider' => 'openai',
                'api_key' => 'test-key',
            ],
        ]);

        $emptyProvider = $this->createMock(AIProviderInterface::class);
        $emptyProvider->method('isAvailable')->willReturn(true);
        $emptyProvider->method('analyze')->willReturn(Explanation::empty());

        $engine = new ExplanationEngine($config, $emptyProvider);
        $context = $this->createContext('API failed error', 'Exception');

        $explanation = $engine->explain($context);

        $this->assertFalse($explanation->isEmpty());
        $this->assertSame('API failed error', $explanation->getMessage());
        $this->assertSame(0.3, $explanation->getConfidence());
        $this->assertStringContainsString('exception of type', $explanation->getCause());
    }

    private function createContext(string $message, string $class): RuntimeContext
    {
        return new RuntimeContext(
            exception: new ExceptionInfo(
                class: $class,
                message: $message,
                code: 0,
                file: '/test/file.php',
                line: 10,
            ),
            stackTrace: new StackTraceInfo(frames: []),
            sourceContext: SourceContext::empty(),
        );
    }
}
