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
}
