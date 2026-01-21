<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Laravel;

use ClarityPHP\RuntimeInsight\Config;
use ClarityPHP\RuntimeInsight\Context\ContextBuilder;
use ClarityPHP\RuntimeInsight\Contracts\AnalyzerInterface;
use ClarityPHP\RuntimeInsight\Contracts\ContextBuilderInterface;
use ClarityPHP\RuntimeInsight\Contracts\ExplanationEngineInterface;
use ClarityPHP\RuntimeInsight\RuntimeInsight;
use ClarityPHP\RuntimeInsight\RuntimeInsightFactory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

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

        // Register ContextBuilder
        $this->app->singleton(ContextBuilderInterface::class, function (Application $app): ContextBuilderInterface {
            return new ContextBuilder($app->make(Config::class));
        });

        // Register ExplanationEngine with all strategies
        $this->app->singleton(ExplanationEngineInterface::class, function (Application $app): ExplanationEngineInterface {
            return RuntimeInsightFactory::createExplanationEngine(
                $app->make(Config::class),
            );
        });

        // Register main RuntimeInsight
        $this->app->singleton(RuntimeInsight::class, function (Application $app): RuntimeInsight {
            return new RuntimeInsight(
                $app->make(ContextBuilderInterface::class),
                $app->make(ExplanationEngineInterface::class),
                $app->make(Config::class),
            );
        });

        $this->app->singleton(AnalyzerInterface::class, RuntimeInsight::class);
        $this->app->alias(RuntimeInsight::class, 'runtime-insight');
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
                // Commands will be added here
            ]);
        }
    }
}
