<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Webhook;

use function is_array;
use function is_bool;
use function is_int;
use function is_string;

/**
 * Optional HTTP webhook delivery after analysis (see Config webhooks section).
 */
final readonly class WebhookSettings
{
    /**
     * @param array<string> $urls
     * @param array<string, string> $headers
     */
    public function __construct(
        private bool $enabled,
        private array $urls,
        private int $timeoutSeconds,
        private array $headers,
    ) {}

    public static function disabled(): self
    {
        return new self(false, [], 3, []);
    }

    /**
     * @param mixed $value
     */
    public static function fromConfigArray(mixed $value): self
    {
        if (! is_array($value)) {
            return self::disabled();
        }

        $enabled = $value['enabled'] ?? false;
        $urlsRaw = $value['urls'] ?? [];
        $timeout = $value['timeout'] ?? 3;
        $headersRaw = $value['headers'] ?? [];

        $urls = [];
        if (is_array($urlsRaw)) {
            foreach ($urlsRaw as $u) {
                if (is_string($u) && $u !== '') {
                    $urls[] = $u;
                }
            }
        }

        $headers = [];
        if (is_array($headersRaw)) {
            foreach ($headersRaw as $k => $v) {
                if (is_string($k) && is_string($v)) {
                    $headers[$k] = $v;
                }
            }
        }

        return new self(
            enabled: is_bool($enabled) ? $enabled : false,
            urls: $urls,
            timeoutSeconds: is_int($timeout) ? max(1, $timeout) : 3,
            headers: $headers,
        );
    }

    public function shouldDeliver(): bool
    {
        return $this->enabled && $this->urls !== [];
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return array<string>
     */
    public function getUrls(): array
    {
        return $this->urls;
    }

    public function getTimeoutSeconds(): int
    {
        return $this->timeoutSeconds;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}
