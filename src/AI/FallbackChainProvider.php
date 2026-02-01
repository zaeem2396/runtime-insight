<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\AI;

use ClarityPHP\RuntimeInsight\Contracts\AIProviderInterface;
use ClarityPHP\RuntimeInsight\DTO\Explanation;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;

/**
 * Tries multiple AI providers in order and returns the first non-empty explanation.
 */
final class FallbackChainProvider implements AIProviderInterface
{
    /**
     * @param array<AIProviderInterface> $providers
     */
    public function __construct(
        private readonly array $providers,
    ) {}

    public function analyze(RuntimeContext $context): Explanation
    {
        foreach ($this->providers as $provider) {
            if (! $provider->isAvailable()) {
                continue;
            }

            $explanation = $provider->analyze($context);

            if (! $explanation->isEmpty()) {
                return $explanation;
            }
        }

        return Explanation::empty();
    }

    public function isAvailable(): bool
    {
        foreach ($this->providers as $provider) {
            if ($provider->isAvailable()) {
                return true;
            }
        }

        return false;
    }

    public function getName(): string
    {
        return 'fallback';
    }
}
