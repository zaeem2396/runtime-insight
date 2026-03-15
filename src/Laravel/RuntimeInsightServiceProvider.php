<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Laravel;

use ClarityPHP\RuntimeInsight\Collectors\CollectorRegistry;
use ClarityPHP\RuntimeInsight\Config;
use ClarityPHP\RuntimeInsight\Context\ContextBuilder;
use ClarityPHP\RuntimeInsight\Contracts\AIProviderInterface;
use ClarityPHP\RuntimeInsight\Contracts\AnalyzerInterface;
use ClarityPHP\RuntimeInsight\Contracts\ContextBuilderInterface;
use ClarityPHP\RuntimeInsight\Contracts\EventDispatcherInterface;
use ClarityPHP\RuntimeInsight\Contracts\ExplanationEngineInterface;
use ClarityPHP\RuntimeInsight\Contracts\LogParserInterface;
use ClarityPHP\RuntimeInsight\Contracts\PatternAnalyzerInterface;
use ClarityPHP\RuntimeInsight\Contracts\RootCauseAnalyzerInterface;
use ClarityPHP\RuntimeInsight\Event\InMemoryEventDispatcher;
use ClarityPHP\RuntimeInsight\Engine\LaravelPatternAnalyzer;
use ClarityPHP\RuntimeInsight\Engine\RootCauseAnalyzer;
use ClarityPHP\RuntimeInsight\Laravel\Commands\AnalyzeCommand;
use ClarityPHP\RuntimeInsight\Laravel\Commands\DoctorCommand;
use ClarityPHP\RuntimeInsight\Laravel\Commands\ExplainCommand;
use ClarityPHP\RuntimeInsight\Laravel\Commands\InstallCommand;
use ClarityPHP\RuntimeInsight\Laravel\Commands\TimelineCommand;
use ClarityPHP\RuntimeInsight\Laravel\Context\LaravelContextBuilder;
use ClarityPHP\RuntimeInsight\Log\LaravelLogParser;
use ClarityPHP\RuntimeInsight\RuntimeInsight;
use ClarityPHP\RuntimeInsight\RuntimeInsightFactory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

use function is_array;

/**
 * Laravel Service Provider for Runtime Insight.
 */
class RuntimeInsightServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/runtime-insight.php',
            'runtime-insight',
        );

        // Register Config
        $this->app->singleton(Config::class, function (Application $app): Config {
            /** @var \Illuminate\Contracts\Config\Repository $configRepository */
            $configRepository = $app->make('config');

            /** @var array<string, mixed>|null $config */
            $config = $configRepository->get('runtime-insight');

            $config = is_array($config) ? $config : [];
            $config['current_environment'] = $app->environment();

            return Config::fromArray($config);
        });

        // Register base ContextBuilder
        $this->app->singleton(ContextBuilder::class, function (Application $app): ContextBuilder {
            return new ContextBuilder($app->make(Config::class));
        });

        // Register Laravel-specific ContextBuilder
        $this->app->singleton(ContextBuilderInterface::class, function (Application $app): ContextBuilderInterface {
            return new LaravelContextBuilder(
                $app->make(ContextBuilder::class),
                $app,
                $app->make(Config::class),
            );
        });

        // Register AI Provider (if configured)
        $this->app->singleton(AIProviderInterface::class, function (Application $app): ?AIProviderInterface {
            $config = $app->make(Config::class);

            return RuntimeInsightFactory::createAIProvider($config);
        });

        // Register ExplanationEngine with all strategies
        $this->app->singleton(ExplanationEngineInterface::class, function (Application $app): ExplanationEngineInterface {
            $config = $app->make(Config::class);
            $aiProvider = $app->bound(AIProviderInterface::class) ? $app->make(AIProviderInterface::class) : null;

            return RuntimeInsightFactory::createExplanationEngine($config, $aiProvider);
        });

        // Register CollectorRegistry and RootCauseAnalyzer for the pipeline
        $this->app->singleton(CollectorRegistry::class, function (Application $app): CollectorRegistry {
            return RuntimeInsightFactory::createDefaultCollectorRegistry();
        });
        $this->app->singleton(RootCauseAnalyzerInterface::class, function (Application $app): RootCauseAnalyzer {
            return new RootCauseAnalyzer();
        });
        $this->app->singleton(PatternAnalyzerInterface::class, function (Application $app): LaravelPatternAnalyzer {
            return new LaravelPatternAnalyzer();
        });
        $this->app->singleton(LogParserInterface::class, function (Application $app): LaravelLogParser {
            return new LaravelLogParser();
        });
        $this->app->singleton(EventDispatcherInterface::class, function (Application $app): InMemoryEventDispatcher {
            return new InMemoryEventDispatcher();
        });

        // Register main RuntimeInsight
        $this->app->singleton(RuntimeInsight::class, function (Application $app): RuntimeInsight {
            return new RuntimeInsight(
                $app->make(ContextBuilderInterface::class),
                $app->make(ExplanationEngineInterface::class),
                $app->make(Config::class),
                $app->make(CollectorRegistry::class),
                $app->make(RootCauseAnalyzerInterface::class),
                $app->make(PatternAnalyzerInterface::class),
                $app->make(EventDispatcherInterface::class),
            );
        });

        $this->app->singleton(AnalyzerInterface::class, RuntimeInsight::class);
        $this->app->alias(RuntimeInsight::class, 'runtime-insight');

        // Register ExceptionHandler
        $this->app->singleton(ExceptionHandler::class, function (Application $app): ExceptionHandler {
            return new ExceptionHandler(
                $app->make(AnalyzerInterface::class),
                $app->make(LoggerInterface::class),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/runtime-insight.php' => config_path('runtime-insight.php'),
            ], 'runtime-insight-config');

            $this->commands([
                ExplainCommand::class,
                AnalyzeCommand::class,
                TimelineCommand::class,
                DoctorCommand::class,
                InstallCommand::class,
            ]);
        }
    }
}
