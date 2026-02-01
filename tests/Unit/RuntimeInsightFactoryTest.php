<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit;

use ClarityPHP\RuntimeInsight\Config;
use ClarityPHP\RuntimeInsight\Contracts\ExplanationEngineInterface;
use ClarityPHP\RuntimeInsight\Engine\CachingExplanationEngine;
use ClarityPHP\RuntimeInsight\Engine\ExplanationEngine;
use ClarityPHP\RuntimeInsight\RuntimeInsight;
use ClarityPHP\RuntimeInsight\RuntimeInsightFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RuntimeInsightFactoryTest extends TestCase
{
    #[Test]
    public function it_creates_runtime_insight_with_default_config(): void
    {
        $insight = RuntimeInsightFactory::create();

        $this->assertInstanceOf(RuntimeInsight::class, $insight);
        $this->assertTrue($insight->isEnabled());
    }

    #[Test]
    public function it_creates_runtime_insight_with_custom_config(): void
    {
        $insight = RuntimeInsightFactory::create([
            'enabled' => false,
        ]);

        $this->assertInstanceOf(RuntimeInsight::class, $insight);
        $this->assertFalse($insight->isEnabled());
    }

    #[Test]
    public function it_creates_explanation_engine_with_all_strategies_when_cache_disabled(): void
    {
        $config = new Config(cacheEnabled: false);
        $engine = RuntimeInsightFactory::createExplanationEngine($config);

        $this->assertInstanceOf(ExplanationEngine::class, $engine);
        $this->assertCount(5, $engine->getStrategies());
    }

    #[Test]
    public function it_wraps_engine_in_caching_engine_when_cache_enabled(): void
    {
        $config = new Config(cacheEnabled: true);
        $engine = RuntimeInsightFactory::createExplanationEngine($config);

        $this->assertInstanceOf(ExplanationEngineInterface::class, $engine);
        $this->assertInstanceOf(CachingExplanationEngine::class, $engine);
    }

    #[Test]
    public function it_creates_runtime_insight_with_config_object(): void
    {
        $config = new Config(
            enabled: true,
            sourceLines: 15,
        );

        $insight = RuntimeInsightFactory::createWithConfig($config);

        $this->assertInstanceOf(RuntimeInsight::class, $insight);
        $this->assertTrue($insight->isEnabled());
    }
}
