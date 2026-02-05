<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Feature\Laravel\Commands;

use ClarityPHP\RuntimeInsight\Contracts\AnalyzerInterface;
use ClarityPHP\RuntimeInsight\DTO\Explanation;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class ExplainCommandTest extends TestCase
{
    private AnalyzerInterface&MockObject $analyzer;

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

    public function test_it_calls_analyze_from_log_with_parsed_message_and_location(): void
    {
        $logPath = $this->writeTempLog(
            '[2025-01-15 12:00:00] local.ERROR: Undefined array key "id" {"exception":"[object] (ErrorException at /app/Http/Controllers/OrderController.php:137)',
        );

        $this->analyzer
            ->method('analyzeFromLog')
            ->with('Undefined array key "id"', '/app/Http/Controllers/OrderController.php', 137)
            ->willReturn(new Explanation(
                message: 'Undefined array key "id"',
                cause: 'Test',
                suggestions: [],
                confidence: 0.88,
                errorType: 'UndefinedIndex',
                location: '/app/Http/Controllers/OrderController.php:137',
            ));

        $this->artisan('runtime:explain', ['--log' => $logPath])
            ->assertExitCode(0);
    }

    private function writeTempLog(string $content): string
    {
        $path = sys_get_temp_dir() . '/runtime-insight-test-' . uniqid() . '.log';
        file_put_contents($path, $content);

        return $path;
    }

    protected function getPackageProviders($app): array
    {
        return [
            \ClarityPHP\RuntimeInsight\Laravel\RuntimeInsightServiceProvider::class,
        ];
    }
}
