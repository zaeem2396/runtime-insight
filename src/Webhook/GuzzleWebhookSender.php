<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Webhook;

use ClarityPHP\RuntimeInsight\Contracts\WebhookSenderInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

/**
 * POSTs JSON payloads to webhook URLs using Guzzle.
 */
final class GuzzleWebhookSender implements WebhookSenderInterface
{
    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly int $timeoutSeconds,
        private readonly ClientInterface $client,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public static function createDefault(int $timeoutSeconds, ?LoggerInterface $logger = null): self
    {
        return new self(
            $timeoutSeconds,
            new Client(['http_errors' => false, 'timeout' => $timeoutSeconds]),
            $logger ?? new NullLogger(),
        );
    }

    /**
     * @param array<string, string> $extraHeaders
     */
    public function post(string $url, string $jsonBody, array $extraHeaders = []): void
    {
        $headers = array_merge(
            ['Content-Type' => 'application/json'],
            $extraHeaders,
        );

        try {
            $response = $this->client->request('POST', $url, [
                'headers' => $headers,
                'body' => $jsonBody,
                'timeout' => $this->timeoutSeconds,
            ]);
            $status = $response->getStatusCode();
            if ($status >= 400) {
                $this->logger->warning('Runtime Insight webhook returned non-success status', [
                    'url' => $url,
                    'status' => $status,
                ]);
            }
        } catch (Throwable $e) {
            $this->logger->warning('Runtime Insight webhook request failed: ' . $e->getMessage(), [
                'url' => $url,
            ]);
        }
    }
}
