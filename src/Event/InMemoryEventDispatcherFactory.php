<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Event;

use ClarityPHP\RuntimeInsight\Config;
use ClarityPHP\RuntimeInsight\Webhook\AfterAnalysisWebhookListener;
use ClarityPHP\RuntimeInsight\Webhook\GuzzleWebhookSender;
use ClarityPHP\RuntimeInsight\Webhook\WebhookPayloadBuilder;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Builds {@see InMemoryEventDispatcher} with optional framework integrations (e.g. webhooks).
 */
final class InMemoryEventDispatcherFactory
{
    public static function create(Config $config, ?LoggerInterface $logger = null): InMemoryEventDispatcher
    {
        $dispatcher = new InMemoryEventDispatcher();
        $logger ??= new NullLogger();

        $webhooks = $config->getWebhookSettings();
        if ($webhooks->shouldDeliver()) {
            $sender = GuzzleWebhookSender::createDefault($webhooks->getTimeoutSeconds(), $logger);
            $listener = new AfterAnalysisWebhookListener(
                $webhooks,
                $sender,
                new WebhookPayloadBuilder(),
                $logger,
            );
            $dispatcher->addListener(AfterAnalysisEvent::class, static function (object $event) use ($listener): void {
                if ($event instanceof AfterAnalysisEvent) {
                    $listener($event);
                }
            });
        }

        return $dispatcher;
    }
}
