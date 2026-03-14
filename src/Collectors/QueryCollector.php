<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Collectors;

use ClarityPHP\RuntimeInsight\Contracts\SignalCollectorInterface;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;

use function count;

/**
 * Collects recent database queries from context (e.g. from Laravel query log).
 */
final class QueryCollector implements SignalCollectorInterface
{
    public function getName(): string
    {
        return 'query';
    }

    public function isEnabled(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function collect(RuntimeContext $context): ?array
    {
        if ($context->databaseContext === null || $context->databaseContext->isEmpty()) {
            return null;
        }

        return [
            'recent_queries' => $context->databaseContext->recentQueries,
            'count' => count($context->databaseContext->recentQueries),
        ];
    }
}
