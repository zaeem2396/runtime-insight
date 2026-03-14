<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Collectors;

use ClarityPHP\RuntimeInsight\Contracts\SignalCollectorInterface;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;

/**
 * Collects sanitized request snapshot (method, URI, route).
 */
final class RequestCollector implements SignalCollectorInterface
{
    public function getName(): string
    {
        return 'request';
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
        if ($context->requestContext === null) {
            return null;
        }

        $r = $context->requestContext;

        return [
            'method' => $r->method,
            'uri' => $r->uri,
            'route' => $r->route,
        ];
    }
}
