<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\AI;

use ClarityPHP\RuntimeInsight\AI\AnthropicProvider;
use ClarityPHP\RuntimeInsight\AI\FallbackChainProvider;
use ClarityPHP\RuntimeInsight\AI\OpenAIProvider;
use ClarityPHP\RuntimeInsight\AI\ProviderFactory;
use ClarityPHP\RuntimeInsight\Config;
use PHPUnit\Framework\TestCase;

final class ProviderFactoryTest extends TestCase
{
    public function test_create_returns_openai_provider_when_configured(): void
    {
        $config = new Config(
            enabled: true,
            aiEnabled: true,
            aiProvider: 'openai',
            aiApiKey: 'test-key',
        );

        $factory = new ProviderFactory();
        $provider = $factory->create($config);

        $this->assertInstanceOf(OpenAIProvider::class, $provider);
        $this->assertSame('openai', $provider->getName());
    }

    public function test_create_returns_anthropic_provider_when_configured(): void
    {
        $config = new Config(
            enabled: true,
            aiEnabled: true,
            aiProvider: 'anthropic',
            aiApiKey: 'test-key',
        );

        $factory = new ProviderFactory();
        $provider = $factory->create($config);

        $this->assertInstanceOf(AnthropicProvider::class, $provider);
        $this->assertSame('anthropic', $provider->getName());
    }

    public function test_create_returns_null_for_unknown_provider(): void
    {
        $config = new Config(
            enabled: true,
            aiEnabled: true,
            aiProvider: 'unknown',
        );

        $factory = new ProviderFactory();
        $provider = $factory->create($config);

        $this->assertNull($provider);
    }

    public function test_create_provider_static_returns_openai_provider(): void
    {
        $config = new Config(
            enabled: true,
            aiEnabled: true,
            aiProvider: 'openai',
            aiApiKey: 'test-key',
        );

        $provider = ProviderFactory::createProvider($config);

        $this->assertInstanceOf(OpenAIProvider::class, $provider);
    }

    public function test_create_provider_static_returns_null_for_unknown(): void
    {
        $config = new Config(
            enabled: true,
            aiEnabled: true,
            aiProvider: 'future-provider',
        );

        $provider = ProviderFactory::createProvider($config);

        $this->assertNull($provider);
    }

    public function test_create_returns_fallback_chain_when_fallback_configured(): void
    {
        $config = new Config(
            enabled: true,
            aiEnabled: true,
            aiProvider: 'openai',
            aiApiKey: 'test-key',
            aiFallback: ['anthropic'],
        );

        $factory = new ProviderFactory();
        $provider = $factory->create($config);

        $this->assertInstanceOf(FallbackChainProvider::class, $provider);
        $this->assertSame('fallback', $provider->getName());
        $this->assertTrue($provider->isAvailable());
    }

    public function test_create_returns_single_provider_when_fallback_empty(): void
    {
        $config = new Config(
            enabled: true,
            aiEnabled: true,
            aiProvider: 'openai',
            aiApiKey: 'test-key',
            aiFallback: [],
        );

        $factory = new ProviderFactory();
        $provider = $factory->create($config);

        $this->assertInstanceOf(OpenAIProvider::class, $provider);
    }

    public function test_create_for_provider_returns_single_provider(): void
    {
        $config = new Config(
            enabled: true,
            aiEnabled: true,
            aiProvider: 'anthropic',
            aiApiKey: 'key',
        );

        $factory = new ProviderFactory();
        $provider = $factory->createForProvider($config);

        $this->assertInstanceOf(AnthropicProvider::class, $provider);
    }
}
