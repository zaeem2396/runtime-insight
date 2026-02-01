<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\AI;

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
}
