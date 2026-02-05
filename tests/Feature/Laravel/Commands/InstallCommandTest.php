<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Feature\Laravel\Commands;

use Orchestra\Testbench\TestCase;

final class InstallCommandTest extends TestCase
{
    public function test_command_exists(): void
    {
        $this->artisan('runtime:install', ['--help'])
            ->assertExitCode(0);
    }

    public function test_it_reports_when_key_already_in_env(): void
    {
        $envPath = base_path('.env');
        file_put_contents($envPath, "APP_KEY=base64:test\nOPEN_AI_APIKEY=sk-xxx\n");

        $this->artisan('runtime:install')
            ->expectsOutputToContain('OPEN_AI_APIKEY is already in your .env file')
            ->assertExitCode(0);
    }

    public function test_it_adds_open_ai_apikey_when_missing(): void
    {
        $envPath = base_path('.env');
        file_put_contents($envPath, "APP_KEY=base64:test\n");

        $this->artisan('runtime:install')
            ->expectsOutputToContain('Added OPEN_AI_APIKEY= to .env')
            ->assertExitCode(0);

        $content = file_get_contents($envPath);
        self::assertStringContainsString('OPEN_AI_APIKEY=', $content);
    }

    protected function getPackageProviders($app): array
    {
        return [
            \ClarityPHP\RuntimeInsight\Laravel\RuntimeInsightServiceProvider::class,
        ];
    }
}
