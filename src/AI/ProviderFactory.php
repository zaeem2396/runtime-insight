<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\AI;

use ClarityPHP\RuntimeInsight\Config;
use ClarityPHP\RuntimeInsight\Contracts\AIProviderInterface;
use Psr\Log\LoggerInterface;

/**
 * Factory for creating AI provider instances based on configuration.
 *
 * Supports: openai, anthropic, ollama.
 */
final class ProviderFactory
{
    public function __construct(
        private readonly ?LoggerInterface $logger = null,
    ) {}

    /**
     * Create an AI provider based on configuration.
     */
    public function create(Config $config): ?AIProviderInterface
    {
        $provider = $config->getAIProvider();

        return match ($provider) {
            'openai' => new OpenAIProvider($config, $this->logger),
            'anthropic' => new AnthropicProvider($config, $this->logger),
            'ollama' => new OllamaProvider($config, $this->logger),
            default => null,
        };
    }

    /**
     * Create an AI provider (static convenience method).
     */
    public static function createProvider(Config $config, ?LoggerInterface $logger = null): ?AIProviderInterface
    {
        $factory = new self($logger);

        return $factory->create($config);
    }
}
