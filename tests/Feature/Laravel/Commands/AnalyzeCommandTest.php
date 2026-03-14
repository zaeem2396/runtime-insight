<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Feature\Laravel\Commands;

use Orchestra\Testbench\TestCase;

final class AnalyzeCommandTest extends TestCase
{
    public function test_command_exists(): void
    {
        $this->artisan('runtime:analyze', ['--help'])
            ->assertExitCode(0);
    }

    public function test_it_reports_no_errors_when_log_empty_or_missing(): void
    {
        $this->artisan('runtime:analyze', ['log' => __DIR__ . '/../../../fixtures/empty.log'])
            ->expectsOutputToContain('No errors found')
            ->assertExitCode(0);
    }

    public function test_it_summarizes_errors_from_log_file(): void
    {
        $log = sys_get_temp_dir() . '/ri-analyze-' . uniqid() . '.log';
        $content = "[2026-02-01 12:00:00] local.ERROR: TypeError (Null given) at /app/Test.php:10\n";
        $content .= "[2026-02-01 12:01:00] local.ERROR: TypeError (Null given) at /app/Test.php:10\n";
        file_put_contents($log, $content);

        try {
            $this->artisan('runtime:analyze', ['log' => $log])
                ->expectsOutputToContain('Total: 2 error(s)')
                ->expectsOutputToContain('Top failures')
                ->assertExitCode(0);
        } finally {
            @unlink($log);
        }
    }

    protected function getPackageProviders($app): array
    {
        return [
            \ClarityPHP\RuntimeInsight\Laravel\RuntimeInsightServiceProvider::class,
        ];
    }
}
