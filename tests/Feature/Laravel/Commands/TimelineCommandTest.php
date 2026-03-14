<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Feature\Laravel\Commands;

use Orchestra\Testbench\TestCase;

final class TimelineCommandTest extends TestCase
{
    public function test_command_exists(): void
    {
        $this->artisan('runtime:timeline', ['--help'])
            ->assertExitCode(0);
    }

    public function test_it_reports_no_events_when_log_empty(): void
    {
        $emptyLog = sys_get_temp_dir() . '/ri-timeline-' . uniqid() . '.log';
        file_put_contents($emptyLog, '');

        try {
            $this->artisan('runtime:timeline', ['log' => $emptyLog])
                ->expectsOutputToContain('No events found')
                ->assertExitCode(0);
        } finally {
            @unlink($emptyLog);
        }
    }

    public function test_it_outputs_timeline_when_log_has_errors(): void
    {
        $log = sys_get_temp_dir() . '/ri-timeline-' . uniqid() . '.log';
        $content = "[2026-02-01 12:00:00] local.ERROR: TypeError (Null given)\n at /app/Test.php:10\n";
        file_put_contents($log, $content);

        try {
            $this->artisan('runtime:timeline', ['log' => $log])
                ->expectsOutputToContain('Runtime Timeline')
                ->expectsOutputToContain('T+')
                ->expectsOutputToContain('Request started')
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
