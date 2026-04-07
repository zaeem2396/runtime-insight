<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Engine\RootCause;

use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;

use function count;
use function implode;

/**
 * Human-readable contributing factors from request, database, and stack summary.
 */
final class ContributingNarrator
{
    /**
     * @param array{narrative: string, vendor_frames: int, app_frames: int, first_app_location: string|null} $stackSummary
     */
    public function narrate(RuntimeContext $context, array $stackSummary): string
    {
        $parts = [];

        if ($context->requestContext !== null) {
            $req = $context->requestContext;
            $parts[] = $req->method . ' ' . $req->uri;
            if ($req->route !== null && $req->route !== '') {
                $parts[] = 'Route: ' . $req->route;
            }
        }

        if ($context->databaseContext !== null && ! $context->databaseContext->isEmpty()) {
            $parts[] = 'Query log shows ' . count($context->databaseContext->recentQueries) . ' query(ies) before failure.';
        }

        if ($stackSummary['narrative'] !== '') {
            $parts[] = $stackSummary['narrative'];
        }

        return $parts === [] ? '' : implode(' ', $parts);
    }
}
