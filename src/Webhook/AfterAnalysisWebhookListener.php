<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Webhook;

use ClarityPHP\RuntimeInsight\Contracts\WebhookSenderInterface;
use ClarityPHP\RuntimeInsight\Event\AfterAnalysisEvent;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

use function json_encode;

/**
 * Listens for {@see AfterAnalysisEvent} and POSTs a JSON payload to configured webhook URLs.
 */
final class AfterAnalysisWebhookListener
{
    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly WebhookSettings $webhooks,
        private readonly WebhookSenderInterface $sender,
        private readonly WebhookPayloadBuilder $payloadBuilder,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function __invoke(AfterAnalysisEvent $event): void
    {
        if (! $this->webhooks->shouldDeliver()) {
            return;
        }

        try {
            $payload = $this->payloadBuilder->build($event);
            $json = json_encode($payload, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            $this->logger->warning('Runtime Insight webhook payload encoding failed: ' . $e->getMessage());

            return;
        }

        foreach ($this->webhooks->getUrls() as $url) {
            $this->sender->post($url, $json, $this->webhooks->getHeaders());
        }
    }
}
