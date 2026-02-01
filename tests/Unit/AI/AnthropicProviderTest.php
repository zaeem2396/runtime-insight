<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\AI;

use ClarityPHP\RuntimeInsight\AI\AnthropicProvider;
use ClarityPHP\RuntimeInsight\Config;
use ClarityPHP\RuntimeInsight\DTO\ExceptionInfo;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use ClarityPHP\RuntimeInsight\DTO\SourceContext;
use ClarityPHP\RuntimeInsight\DTO\StackTraceInfo;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class AnthropicProviderTest extends TestCase
{
    private Config $config;

    private LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new Config(
            enabled: true,
            aiEnabled: true,
            aiProvider: 'anthropic',
            aiApiKey: 'test-api-key',
            aiModel: 'claude-sonnet-4-20250514',
        );

        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function test_it_returns_empty_when_not_available(): void
    {
        $config = new Config(
            enabled: true,
            aiEnabled: false,
        );

        $provider = new AnthropicProvider($config);
        $context = $this->createMockContext();

        $result = $provider->analyze($context);

        $this->assertTrue($result->isEmpty());
    }

    public function test_it_returns_empty_when_api_key_missing(): void
    {
        $config = new Config(
            enabled: true,
            aiEnabled: true,
            aiProvider: 'anthropic',
            aiApiKey: null,
        );

        $provider = new AnthropicProvider($config);

        $this->assertFalse($provider->isAvailable());
    }

    public function test_it_is_available_when_properly_configured(): void
    {
        $provider = new AnthropicProvider($this->config);

        $this->assertTrue($provider->isAvailable());
        $this->assertSame('anthropic', $provider->getName());
    }

    public function test_it_parses_json_response_correctly(): void
    {
        $jsonContent = json_encode([
            'message' => 'Test error message',
            'cause' => 'Root cause explanation',
            'suggestions' => ['Fix 1', 'Fix 2'],
            'confidence' => 0.85,
        ], JSON_THROW_ON_ERROR);

        $mockResponse = [
            'content' => [
                ['type' => 'text', 'text' => $jsonContent],
            ],
            'usage' => [
                'input_tokens' => 100,
                'output_tokens' => 50,
            ],
        ];

        $mock = new MockHandler([
            new Response(200, [], json_encode($mockResponse)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new AnthropicProvider($this->config, null, $client);
        $context = $this->createMockContext();

        $result = $provider->analyze($context);

        $this->assertFalse($result->isEmpty());
        $this->assertSame('Test error message', $result->getMessage());
        $this->assertSame('Root cause explanation', $result->getCause());
        $this->assertCount(2, $result->getSuggestions());
        $this->assertSame(0.85, $result->getConfidence());
        $this->assertSame(150, $result->getMetadata()['tokens_used']);
    }

    public function test_it_handles_text_response_when_json_fails(): void
    {
        $mockResponse = [
            'content' => [
                ['type' => 'text', 'text' => 'This is a text response without JSON'],
            ],
        ];

        $mock = new MockHandler([
            new Response(200, [], json_encode($mockResponse)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new AnthropicProvider($this->config, null, $client);
        $context = $this->createMockContext();

        $result = $provider->analyze($context);

        $this->assertFalse($result->isEmpty());
        $this->assertStringContainsString('text response', $result->getCause());
    }

    public function test_it_handles_api_errors_gracefully(): void
    {
        $mock = new MockHandler([
            new ClientException('API Error', new Request('POST', 'messages'), new Response(500)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $this->logger
            ->expects($this->once())
            ->method('error');

        $provider = new AnthropicProvider($this->config, $this->logger, $client);
        $context = $this->createMockContext();

        $result = $provider->analyze($context);

        $this->assertTrue($result->isEmpty());
    }

    public function test_it_handles_empty_content(): void
    {
        $mockResponse = [
            'content' => [],
        ];

        $mock = new MockHandler([
            new Response(200, [], json_encode($mockResponse)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new AnthropicProvider($this->config, null, $client);
        $context = $this->createMockContext();

        $result = $provider->analyze($context);

        $this->assertTrue($result->isEmpty());
    }

    public function test_it_is_not_available_when_provider_is_openai(): void
    {
        $config = new Config(
            enabled: true,
            aiEnabled: true,
            aiProvider: 'openai',
            aiApiKey: 'key',
        );

        $provider = new AnthropicProvider($config);

        $this->assertFalse($provider->isAvailable());
    }

    private function createMockContext(): RuntimeContext
    {
        $exception = new Exception('Test error');
        $exceptionInfo = ExceptionInfo::fromThrowable($exception);

        return new RuntimeContext(
            exception: $exceptionInfo,
            stackTrace: new StackTraceInfo([]),
            sourceContext: SourceContext::empty(),
        );
    }
}
