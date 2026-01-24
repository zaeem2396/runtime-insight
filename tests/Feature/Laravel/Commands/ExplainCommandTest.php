<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Feature\Laravel\Commands;

use ClarityPHP\RuntimeInsight\Contracts\AnalyzerInterface;
use ClarityPHP\RuntimeInsight\DTO\Explanation;
use ClarityPHP\RuntimeInsight\Laravel\ExceptionHandler;
use Illuminate\Contracts\Logging\Log;
use Orchestra\Testbench\TestCase;
use Psr\Log\LoggerInterface;
use TypeError;

final class ExplainCommandTest extends TestCase
{
    private AnalyzerInterface $analyzer;

    protected function getPackageProviders($app): array
    {
        return [
            \ClarityPHP\RuntimeInsight\Laravel\RuntimeInsightServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->analyzer = $this->createMock(AnalyzerInterface::class);

        // Override analyzer with mock
        $this->app->singleton(AnalyzerInterface::class, fn () => $this->analyzer);
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
}

