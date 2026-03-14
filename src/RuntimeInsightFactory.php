<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight;

use ClarityPHP\RuntimeInsight\AI\ProviderFactory;
use ClarityPHP\RuntimeInsight\Collectors\CacheCollector;
use ClarityPHP\RuntimeInsight\Collectors\CollectorRegistry;
use ClarityPHP\RuntimeInsight\Collectors\ExceptionCollector;
use ClarityPHP\RuntimeInsight\Collectors\MemoryCollector;
use ClarityPHP\RuntimeInsight\Collectors\QueryCollector;
use ClarityPHP\RuntimeInsight\Collectors\QueueCollector;
use ClarityPHP\RuntimeInsight\Collectors\RequestCollector;
use ClarityPHP\RuntimeInsight\Context\ContextBuilder;
use ClarityPHP\RuntimeInsight\Contracts\AIProviderInterface;
use ClarityPHP\RuntimeInsight\Contracts\ContextBuilderInterface;
use ClarityPHP\RuntimeInsight\Contracts\ExplanationEngineInterface;
use ClarityPHP\RuntimeInsight\Contracts\PatternAnalyzerInterface;
use ClarityPHP\RuntimeInsight\Contracts\RootCauseAnalyzerInterface;
use ClarityPHP\RuntimeInsight\Engine\ArrayExplanationCache;
use ClarityPHP\RuntimeInsight\Engine\CachingExplanationEngine;
use ClarityPHP\RuntimeInsight\Engine\ExplanationEngine;
use ClarityPHP\RuntimeInsight\Engine\LaravelPatternAnalyzer;
use ClarityPHP\RuntimeInsight\Engine\RootCauseAnalyzer;
use ClarityPHP\RuntimeInsight\Engine\Strategies\ArgumentCountStrategy;
use ClarityPHP\RuntimeInsight\Engine\Strategies\ClassNotFoundStrategy;
use ClarityPHP\RuntimeInsight\Engine\Strategies\DivisionByZeroErrorStrategy;
use ClarityPHP\RuntimeInsight\Engine\Strategies\NullPointerStrategy;
use ClarityPHP\RuntimeInsight\Engine\Strategies\ParseErrorStrategy;
use ClarityPHP\RuntimeInsight\Engine\Strategies\TypeErrorStrategy;
use ClarityPHP\RuntimeInsight\Engine\Strategies\UndefinedIndexStrategy;
use ClarityPHP\RuntimeInsight\Engine\Strategies\ValueErrorStrategy;

/**
 * Factory for creating RuntimeInsight instances.
 *
 * Provides a simple way to create a fully configured RuntimeInsight
 * instance with all default strategies registered.
 */
final class RuntimeInsightFactory
{
    /**
     * Create a RuntimeInsight instance with default configuration.
     *
     * @param array<string, mixed> $config
     */
    public static function create(array $config = []): RuntimeInsight
    {
        $configObject = Config::fromArray($config);

        return self::createWithConfig($configObject);
    }

    /**
     * Create a RuntimeInsight instance with a Config object.
     */
    public static function createWithConfig(
        Config $config,
        ?ContextBuilderInterface $contextBuilder = null,
        ?ExplanationEngineInterface $explanationEngine = null,
        ?AIProviderInterface $aiProvider = null,
        ?CollectorRegistry $collectorRegistry = null,
        ?RootCauseAnalyzerInterface $rootCauseAnalyzer = null,
        ?PatternAnalyzerInterface $patternAnalyzer = null,
    ): RuntimeInsight {
        $contextBuilder ??= new ContextBuilder($config);
        $explanationEngine ??= self::createExplanationEngine($config, $aiProvider);
        $collectorRegistry ??= self::createDefaultCollectorRegistry();
        $rootCauseAnalyzer ??= new RootCauseAnalyzer();
        $patternAnalyzer ??= new LaravelPatternAnalyzer();

        return new RuntimeInsight($contextBuilder, $explanationEngine, $config, $collectorRegistry, $rootCauseAnalyzer, $patternAnalyzer);
    }

    /**
     * Create default collector registry with all built-in collectors.
     */
    public static function createDefaultCollectorRegistry(): CollectorRegistry
    {
        $registry = new CollectorRegistry();
        $registry->addCollector(new ExceptionCollector());
        $registry->addCollector(new QueryCollector());
        $registry->addCollector(new RequestCollector());
        $registry->addCollector(new MemoryCollector());
        $registry->addCollector(new QueueCollector());
        $registry->addCollector(new CacheCollector());

        return $registry;
    }

    /**
     * Create the explanation engine with all default strategies.
     * Wraps in CachingExplanationEngine when cache is enabled.
     */
    public static function createExplanationEngine(
        Config $config,
        ?AIProviderInterface $aiProvider = null,
    ): ExplanationEngineInterface {
        // Create AI provider if not provided and AI is enabled
        if ($aiProvider === null && $config->isAIConfigured()) {
            $aiProvider = self::createAIProvider($config);
        }

        $engine = new ExplanationEngine($config, $aiProvider);

        // Register all default strategies (highest priority first)
        $engine->addStrategy(new NullPointerStrategy());
        $engine->addStrategy(new UndefinedIndexStrategy());
        $engine->addStrategy(new TypeErrorStrategy());
        $engine->addStrategy(new ArgumentCountStrategy());
        $engine->addStrategy(new ClassNotFoundStrategy());
        $engine->addStrategy(new DivisionByZeroErrorStrategy());
        $engine->addStrategy(new ParseErrorStrategy());
        $engine->addStrategy(new ValueErrorStrategy());

        if ($config->isCacheEnabled()) {
            return new CachingExplanationEngine($engine, new ArrayExplanationCache(), $config);
        }

        return $engine;
    }

    /**
     * Create an AI provider based on configuration.
     */
    public static function createAIProvider(Config $config): ?AIProviderInterface
    {
        return ProviderFactory::createProvider($config);
    }
}
