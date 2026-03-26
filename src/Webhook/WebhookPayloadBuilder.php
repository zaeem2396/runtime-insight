<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Webhook;

use ClarityPHP\RuntimeInsight\Event\AfterAnalysisEvent;

/**
 * Builds a JSON-serializable payload for webhook POST bodies.
 */
final class WebhookPayloadBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(AfterAnalysisEvent $event): array
    {
        $context = $event->context;
        $explanation = $event->explanation;

        return [
            'event' => 'runtime_insight.after_analysis',
            'exception' => $context->exception->toArray(),
            'explanation' => $explanation->toArray(),
        ];
    }
}
