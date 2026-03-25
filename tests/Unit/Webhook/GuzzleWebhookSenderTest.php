<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Webhook;

use ClarityPHP\RuntimeInsight\Webhook\GuzzleWebhookSender;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class GuzzleWebhookSenderTest extends TestCase
{
    #[Test]
    public function post_sends_post_with_json_body(): void
    {
        $mock = new MockHandler([new Response(204)]);
        $client = new Client(['handler' => HandlerStack::create($mock), 'http_errors' => false]);
        $sender = new GuzzleWebhookSender(2, $client);
        $sender->post('https://example.test/hook', '{"a":1}', ['X-Foo' => 'bar']);

        $this->assertCount(0, $mock); // request consumed
    }

    #[Test]
    public function post_logs_on_http_error_status(): void
    {
        $mock = new MockHandler([new Response(500)]);
        $client = new Client(['handler' => HandlerStack::create($mock), 'http_errors' => false]);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning');
        $sender = new GuzzleWebhookSender(2, $client, $logger);
        $sender->post('https://example.test/hook', '{}');
    }
}
