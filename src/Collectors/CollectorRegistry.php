<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Collectors;

use ClarityPHP\RuntimeInsight\Contracts\SignalCollectorInterface;
use ClarityPHP\RuntimeInsight\DTO\CollectorsContext;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;

/**
 * Runs registered signal collectors and attaches payload to context.
 */
final class CollectorRegistry
{
    /**
     * @var array<SignalCollectorInterface>
     */
    private array $collectors = [];

    public function addCollector(SignalCollectorInterface $collector): self
    {
        $this->collectors[] = $collector;

        return $this;
    }

    /**
     * Run collectors and return context with CollectorsContext attached.
     */
    public function enrich(RuntimeContext $context): RuntimeContext
    {
        $signals = [];

        foreach ($this->collectors as $collector) {
            if (! $collector->isEnabled()) {
                continue;
            }

            $payload = $collector->collect($context);
            if ($payload !== null) {
                $signals[$collector->getName()] = $payload;
            }
        }

        if ($signals === []) {
            return $context;
        }

        return $context->withCollectorsContext(new CollectorsContext($signals));
    }

    /**
     * @return array<SignalCollectorInterface>
     */
    public function getCollectors(): array
    {
        return $this->collectors;
    }
}
