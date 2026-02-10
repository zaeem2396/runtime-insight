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
            ->with('Undefined array key "id"', '/app/Http/Controllers/OrderController.php', 137, 'ErrorException')
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

    public function test_it_batch_analyzes_all_exceptions_with_all_flag(): void
    {
        $logPath = $this->writeTempLog(
            "[2025-01-15 12:00:00] local.ERROR: First error at /app/one.php:10\n"
            . "[2025-01-15 12:01:00] local.ERROR: Second error at /app/two.php:20\n",
        );

        $this->analyzer
            ->expects($this->exactly(2))
            ->method('analyzeFromLog')
            ->willReturn(new Explanation(
                message: 'Test',
                cause: 'Test cause',
                suggestions: [],
                confidence: 0.8,
                location: null,
            ));

        $this->artisan('runtime:explain', ['--log' => $logPath, '--all' => true])
            ->expectsOutputToContain('Exception 1 / 2')
            ->expectsOutputToContain('Exception 2 / 2')
            ->assertExitCode(0);
    }

    public function test_it_parses_exception_class_from_log_for_strategy_matching(): void
    {
        $logPath = $this->writeTempLog(
            '[2025-01-15 12:00:00] local.ERROR: Unsupported operand types {"exception":"[object] (TypeError at /app/Http/Controllers/CalcController.php:22)',
        );

        $this->analyzer
            ->method('analyzeFromLog')
            ->with(
                'Unsupported operand types',
                '/app/Http/Controllers/CalcController.php',
                22,
                'TypeError',
            )
            ->willReturn(new Explanation(
                message: 'Unsupported operand types',
                cause: 'Type mismatch',
                suggestions: [],
                confidence: 0.9,
                location: '/app/Http/Controllers/CalcController.php:22',
            ));

        $this->artisan('runtime:explain', ['--log' => $logPath])
            ->assertExitCode(0);
    }

    public function test_it_writes_explanation_to_file_when_output_option_set(): void
    {
        $logPath = $this->writeTempLog(
            '[2025-01-15 12:00:00] local.ERROR: Test error at /app/Test.php:1',
        );
        $outPath = sys_get_temp_dir() . '/runtime-insight-out-' . uniqid() . '.txt';

        $this->analyzer
            ->method('analyzeFromLog')
            ->willReturn(new Explanation(
                message: 'Test error',
                cause: 'Test cause',
                suggestions: ['Fix it'],
                confidence: 0.9,
                location: '/app/Test.php:1',
            ));

        $this->artisan('runtime:explain', ['--log' => $logPath, '--output' => $outPath])
            ->expectsOutputToContain('Explanation written to')
            ->assertExitCode(0);

        $this->assertFileExists($outPath);
        $content = file_get_contents($outPath);
        $this->assertStringContainsString('Test error', $content);
        $this->assertStringContainsString('Test cause', $content);
    }

    protected function getPackageProviders($app): array
    {
        return [
            \ClarityPHP\RuntimeInsight\Laravel\RuntimeInsightServiceProvider::class,
        ];
    }

    private function writeTempLog(string $content): string
    {
        $path = sys_get_temp_dir() . '/runtime-insight-test-' . uniqid() . '.log';
        file_put_contents($path, $content);

        return $path;
    }
}
