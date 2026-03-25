<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Contracts;

/**
 * Sends webhook HTTP requests (implemented by {@see \ClarityPHP\RuntimeInsight\Webhook\GuzzleWebhookSender}).
 */
interface WebhookSenderInterface
{
    /**
     * @param array<string, string> $extraHeaders
     */
    public function post(string $url, string $jsonBody, array $extraHeaders = []): void;
}
