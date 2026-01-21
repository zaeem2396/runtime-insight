<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Contracts;

use ClarityPHP\RuntimeInsight\DTO\Explanation;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;

/**
 * Interface for AI providers that generate explanations.
 */
interface AIProviderInterface
{
    /**
     * Analyze runtime context using AI and generate an explanation.
     */
    public function analyze(RuntimeContext $context): Explanation;

    /**
     * Check if the AI provider is available and configured.
     */
    public function isAvailable(): bool;

    /**
     * Get the provider name.
     */
    public function getName(): string;
}

