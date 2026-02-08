<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Feature\Laravel\Commands;

use ClarityPHP\RuntimeInsight\Contracts\AnalyzerInterface;
use ClarityPHP\RuntimeInsight\DTO\Explanation;
use Orchestra\Testbench\TestCase;
use Throwable;

final class DoctorCommandTest extends TestCase
{
    private AnalyzerInterface $analyzer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->analyzer = $this->createMock(AnalyzerInterface::class);
        $this->app->singleton(AnalyzerInterface::class, fn() => $this->analyzer);
    }

    public function test_command_exists(): void
    {
        // Verify the command is registered
        $this->artisan('runtime:doctor', ['--help'])
            ->assertExitCode(0);
    }

    public function test_it_runs_without_crashing(): void
    {
        $explanation = new Explanation(
            message: 'Test',
            cause: 'Test cause',
            suggestions: [],
            confidence: 0.8,
        );

        $this->analyzer
            ->method('analyze')
            ->willReturn($explanation);

        // Should run without throwing exceptions
        try {
            $this->artisan('runtime:doctor');
            $this->assertTrue(true); // If we get here, command ran successfully
        } catch (Throwable $e) {
            $this->fail('Command threw exception: ' . $e->getMessage());
        }
    }

    public function test_it_suggests_both_env_vars_when_api_key_missing(): void
    {
        $this->app['config']->set('runtime-insight.ai.api_key', null);

        $this->artisan('runtime:doctor')
            ->expectsOutputToContain('No OpenAI API key found')
            ->expectsOutputToContain('RUNTIME_INSIGHT_AI_KEY')
            ->assertExitCode(1);
    }

    protected function getPackageProviders($app): array
    {
        return [
            \ClarityPHP\RuntimeInsight\Laravel\RuntimeInsightServiceProvider::class,
        ];
    }
}
