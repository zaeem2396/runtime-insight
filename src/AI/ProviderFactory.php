<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\AI;

use ClarityPHP\RuntimeInsight\Config;
use ClarityPHP\RuntimeInsight\Contracts\AIProviderInterface;
use Psr\Log\LoggerInterface;

use function count;

/**
 * Factory for creating AI provider instances based on configuration.
 *
 * Supports: openai, anthropic, ollama. Optional fallback chain when ai.fallback is set.
 */
final class ProviderFactory
{
    public function __construct(
        private readonly ?LoggerInterface $logger = null,
    ) {}

    /**
     * Create an AI provider based on configuration.
     * If ai.fallback is non-empty, returns a FallbackChainProvider trying primary then fallbacks.
     */
    public function create(Config $config): ?AIProviderInterface
    {
        $primary = $this->createForProvider($config);

        if ($primary === null) {
            return null;
        }

        $fallbackNames = $config->getAIFallback();

        if ($fallbackNames === []) {
            return $primary;
        }

        $chain = [$primary];

        foreach ($fallbackNames as $name) {
            if ($name === $config->getAIProvider()) {
                continue;
            }

            $provider = $this->createForProvider($config->withProvider($name));

            if ($provider !== null) {
                $chain[] = $provider;
            }
        }

        if (count($chain) === 1) {
            return $primary;
        }

        return new FallbackChainProvider($chain);
    }

    /**
     * Create a single provider for the given config (uses config's current ai.provider).
     */
    public function createForProvider(Config $config): ?AIProviderInterface
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
