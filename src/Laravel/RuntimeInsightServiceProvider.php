<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Laravel;

use ClarityPHP\RuntimeInsight\Config;
use ClarityPHP\RuntimeInsight\Contracts\AnalyzerInterface;
use ClarityPHP\RuntimeInsight\RuntimeInsight;
use Illuminate\Support\ServiceProvider;

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

        $this->app->singleton(Config::class, function ($app): Config {
            $config = $app['config']->get('runtime-insight', []);
            $config['current_environment'] = $app->environment();

            return Config::fromArray($config);
        });

        $this->app->singleton(AnalyzerInterface::class, RuntimeInsight::class);
        $this->app->alias(AnalyzerInterface::class, 'runtime-insight');
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

