<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Engine;

use ClarityPHP\RuntimeInsight\Config;
use ClarityPHP\RuntimeInsight\Contracts\AIProviderInterface;
use ClarityPHP\RuntimeInsight\Contracts\ExplanationEngineInterface;
use ClarityPHP\RuntimeInsight\Contracts\ExplanationStrategyInterface;
use ClarityPHP\RuntimeInsight\DTO\Explanation;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;

use function usort;

/**
 * Engine that produces explanations from runtime context.
 *
 * Uses a chain of strategies (rule-based first, then AI if enabled).
 */
final class ExplanationEngine implements ExplanationEngineInterface
{
    /**
     * @var array<ExplanationStrategyInterface>
     */
    private array $strategies = [];

    public function __construct(
        private readonly Config $config,
        private readonly ?AIProviderInterface $aiProvider = null,
    ) {}

    /**
     * Register an explanation strategy.
     */
    public function addStrategy(ExplanationStrategyInterface $strategy): self
    {
        $this->strategies[] = $strategy;

        // Sort by priority (highest first)
        usort(
            $this->strategies,
            static fn (ExplanationStrategyInterface $a, ExplanationStrategyInterface $b): int => $b->priority() <=> $a->priority(),
        );

        return $this;
    }

    /**
     * Generate an explanation from runtime context.
     */
    public function explain(RuntimeContext $context): Explanation
    {
        // Try rule-based strategies first
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($context)) {
                return $strategy->explain($context);
            }
        }

        // Fall back to AI if enabled and available
        if ($this->config->isAIEnabled() && $this->aiProvider !== null && $this->aiProvider->isAvailable()) {
            return $this->aiProvider->analyze($context);
        }

        // Return a basic explanation if no strategy matched
        return $this->buildFallbackExplanation($context);
    }

    /**
     * Build a basic fallback explanation when no strategy matches.
     */
    private function buildFallbackExplanation(RuntimeContext $context): Explanation
    {
        return new Explanation(
            message: $context->exception->message,
            cause: "An exception of type {$context->exception->class} was thrown.",
            suggestions: [
                'Review the stack trace for more context',
                'Check the error message for specific details',
                "Look at the code near {$context->exception->file}:{$context->exception->line}",
            ],
            confidence: 0.3,
            errorType: $context->exception->class,
            location: "{$context->exception->file}:{$context->exception->line}",
        );
    }

    /**
     * Get all registered strategies.
     *
     * @return array<ExplanationStrategyInterface>
     */
    public function getStrategies(): array
    {
        return $this->strategies;
    }
}

