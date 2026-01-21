<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\DTO;

/**
 * HTTP request context (sanitized).
 */
final readonly class RequestContext
{
    /**
     * @param array<string, mixed> $headers
     * @param array<string, mixed> $query
     * @param array<string, mixed> $body
     */
    public function __construct(
        public string $method,
        public string $uri,
        public array $headers = [],
        public array $query = [],
        public array $body = [],
        public ?string $clientIp = null,
        public ?string $userAgent = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'method' => $this->method,
            'uri' => $this->uri,
            'headers' => $this->headers,
            'query' => $this->query,
            'body' => $this->body,
            'client_ip' => $this->clientIp,
            'user_agent' => $this->userAgent,
        ];
    }
}
