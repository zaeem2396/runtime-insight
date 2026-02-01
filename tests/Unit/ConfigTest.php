<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit;

use ClarityPHP\RuntimeInsight\Config;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    #[Test]
    public function it_creates_from_array(): void
    {
        $config = Config::fromArray([
            'enabled' => true,
            'ai' => [
                'enabled' => true,
                'provider' => 'anthropic',
                'model' => 'claude-sonnet-4-20250514',
                'api_key' => 'test-key',
                'timeout' => 10,
            ],
            'context' => [
                'source_lines' => 15,
                'include_request' => false,
            ],
            'environments' => ['local'],
        ]);

        $this->assertTrue($config->isEnabled());
        $this->assertTrue($config->isAIEnabled());
        $this->assertSame('anthropic', $config->getAIProvider());
        $this->assertSame('claude-sonnet-4-20250514', $config->getAIModel());
        $this->assertSame('test-key', $config->getAIApiKey());
        $this->assertSame(10, $config->getAITimeout());
        $this->assertSame(15, $config->getSourceLines());
        $this->assertFalse($config->shouldIncludeRequest());
    }

    #[Test]
    public function it_uses_default_values(): void
    {
        $config = Config::fromArray([]);

        $this->assertTrue($config->isEnabled());
        $this->assertFalse($config->isAIEnabled()); // No API key
        $this->assertSame('openai', $config->getAIProvider());
        $this->assertSame('gpt-4.1-mini', $config->getAIModel());
        $this->assertSame(10, $config->getSourceLines());
        $this->assertTrue($config->shouldIncludeRequest());
        $this->assertTrue($config->shouldSanitizeInputs());
    }

    #[Test]
    public function it_respects_environment_restrictions(): void
    {
        $config = new Config(
            enabled: true,
            environments: ['local', 'staging'],
            disabledEnvironments: ['production'],
            currentEnvironment: 'production',
        );

        $this->assertFalse($config->isEnabled());
    }

    #[Test]
    public function it_enables_for_allowed_environment(): void
    {
        $config = new Config(
            enabled: true,
            environments: ['local', 'staging'],
            disabledEnvironments: ['production'],
            currentEnvironment: 'local',
        );

        $this->assertTrue($config->isEnabled());
    }

    #[Test]
    public function it_requires_api_key_for_ai(): void
    {
        $configWithoutKey = new Config(
            aiEnabled: true,
            aiApiKey: null,
        );

        $configWithKey = new Config(
            aiEnabled: true,
            aiApiKey: 'sk-test',
        );

        $this->assertFalse($configWithoutKey->isAIEnabled());
        $this->assertTrue($configWithKey->isAIEnabled());
    }

    #[Test]
    public function it_reads_base_url_from_array(): void
    {
        $config = Config::fromArray([
            'ai' => [
                'provider' => 'ollama',
                'model' => 'llama3.2',
                'base_url' => 'http://localhost:11434',
            ],
        ]);

        $this->assertSame('http://localhost:11434', $config->getAIBaseUrl());
    }

    #[Test]
    public function it_reads_fallback_from_array(): void
    {
        $config = Config::fromArray([
            'ai' => [
                'provider' => 'openai',
                'fallback' => ['anthropic', 'ollama'],
            ],
        ]);

        $this->assertSame(['anthropic', 'ollama'], $config->getAIFallback());
    }

    #[Test]
    public function it_returns_empty_fallback_by_default(): void
    {
        $config = Config::fromArray([]);

        $this->assertSame([], $config->getAIFallback());
    }

    #[Test]
    public function with_provider_returns_new_config_with_that_provider(): void
    {
        $config = new Config(
            enabled: true,
            aiEnabled: true,
            aiProvider: 'openai',
            aiModel: 'gpt-4.1-mini',
            aiApiKey: 'key',
        );

        $withAnthropic = $config->withProvider('anthropic');

        $this->assertSame('anthropic', $withAnthropic->getAIProvider());
        $this->assertSame('openai', $config->getAIProvider());
        $this->assertSame('gpt-4.1-mini', $withAnthropic->getAIModel());
    }

    #[Test]
    public function it_reads_cache_from_array(): void
    {
        $config = Config::fromArray([
            'cache' => [
                'enabled' => false,
                'ttl' => 1800,
            ],
        ]);

        $this->assertFalse($config->isCacheEnabled());
        $this->assertSame(1800, $config->getCacheTtl());
    }

    #[Test]
    public function it_uses_default_cache_when_not_in_array(): void
    {
        $config = Config::fromArray([]);

        $this->assertTrue($config->isCacheEnabled());
        $this->assertSame(3600, $config->getCacheTtl());
    }

    #[Test]
    public function with_provider_preserves_cache_settings(): void
    {
        $config = new Config(
            aiProvider: 'openai',
            cacheEnabled: false,
            cacheTtl: 900,
        );

        $withAnthropic = $config->withProvider('anthropic');

        $this->assertSame('anthropic', $withAnthropic->getAIProvider());
        $this->assertFalse($withAnthropic->isCacheEnabled());
        $this->assertSame(900, $withAnthropic->getCacheTtl());
    }

    #[Test]
    public function it_reads_database_context_from_array(): void
    {
        $config = Config::fromArray([
            'context' => [
                'include_database_queries' => true,
                'max_database_queries' => 10,
            ],
        ]);

        $this->assertTrue($config->includeDatabaseQueries());
        $this->assertSame(10, $config->getMaxDatabaseQueries());
    }

    #[Test]
    public function it_uses_default_database_context_when_not_in_array(): void
    {
        $config = Config::fromArray([]);

        $this->assertFalse($config->includeDatabaseQueries());
        $this->assertSame(5, $config->getMaxDatabaseQueries());
    }
}
