<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Feature\Laravel\Commands;

use ClarityPHP\RuntimeInsight\Contracts\AnalyzerInterface;
use Orchestra\Testbench\TestCase;

final class ExplainCommandTest extends TestCase
{
    private AnalyzerInterface $analyzer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->analyzer = $this->createMock(AnalyzerInterface::class);

        // Override analyzer with mock
        $this->app->singleton(AnalyzerInterface::class, fn() => $this->analyzer);

        // Ensure OpenAI API key check passes so command can reach "no exception" path
        $this->app['config']->set('runtime-insight.ai.api_key', 'test-key');
    }

    public function test_it_handles_missing_exception(): void
    {
        // When no exception is found, command should exit with failure
        $this->artisan('runtime:explain')
            ->expectsOutput('No exception found to analyze.')
            ->assertExitCode(1);
    }

    public function test_command_exists(): void
    {
        // Verify the command is registered
        $this->artisan('runtime:explain', ['--help'])
            ->assertExitCode(0);
    }

    public function test_it_fails_when_openai_api_key_is_missing(): void
    {
        // With OpenAI as provider and no API key, command should fail with clear message
        $this->app['config']->set('runtime-insight.ai.api_key', null);

        $this->artisan('runtime:explain')
            ->expectsOutputToContain('No OpenAI API key found')
            ->assertExitCode(1);
    }

    protected function getPackageProviders($app): array
    {
        return [
            \ClarityPHP\RuntimeInsight\Laravel\RuntimeInsightServiceProvider::class,
        ];
    }
}
