<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\AI;

use ClarityPHP\RuntimeInsight\AI\OpenAIProvider;
use ClarityPHP\RuntimeInsight\Config;
use ClarityPHP\RuntimeInsight\DTO\ExceptionInfo;
use ClarityPHP\RuntimeInsight\DTO\Explanation;
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

final class OpenAIProviderTest extends TestCase
{
    private Config $config;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new Config(
            enabled: true,
            aiEnabled: true,
            aiProvider: 'openai',
            aiApiKey: 'test-api-key',
            aiModel: 'gpt-4.1-mini',
        );

        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function test_it_returns_empty_when_not_available(): void
    {
        $config = new Config(
            enabled: true,
            aiEnabled: false,
        );

        $provider = new OpenAIProvider($config);
        $context = $this->createMockContext();

        $result = $provider->analyze($context);

        $this->assertTrue($result->isEmpty());
    }

    public function test_it_returns_empty_when_api_key_missing(): void
    {
        $config = new Config(
            enabled: true,
            aiEnabled: true,
            aiProvider: 'openai',
            aiApiKey: null,
        );

        $provider = new OpenAIProvider($config);

        $this->assertFalse($provider->isAvailable());
    }

    public function test_it_is_available_when_properly_configured(): void
    {
        $provider = new OpenAIProvider($this->config);

        $this->assertTrue($provider->isAvailable());
        $this->assertSame('openai', $provider->getName());
    }

    public function test_it_parses_json_response_correctly(): void
    {
        $mockResponse = [
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'message' => 'Test error message',
                            'cause' => 'Root cause explanation',
                            'suggestions' => ['Fix 1', 'Fix 2'],
                            'confidence' => 0.85,
                        ]),
                    ],
                ],
            ],
            'usage' => [
                'total_tokens' => 150,
            ],
        ];

        $mock = new MockHandler([
            new Response(200, [], json_encode($mockResponse)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new OpenAIProvider($this->config, null, $client);
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
            'choices' => [
                [
                    'message' => [
                        'content' => 'This is a text response without JSON',
                    ],
                ],
            ],
        ];

        $mock = new MockHandler([
            new Response(200, [], json_encode($mockResponse)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new OpenAIProvider($this->config, null, $client);
        $context = $this->createMockContext();

        $result = $provider->analyze($context);

        $this->assertFalse($result->isEmpty());
        $this->assertStringContainsString('text response', $result->getCause());
    }

    public function test_it_handles_rate_limit_with_retry(): void
    {
        $mockResponse = [
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'message' => 'Success after retry',
                            'cause' => 'Test',
                            'suggestions' => [],
                            'confidence' => 0.8,
                        ]),
                    ],
                ],
            ],
        ];

        $mock = new MockHandler([
            new Response(429, [], 'Rate limit exceeded'),
            new Response(200, [], json_encode($mockResponse)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new OpenAIProvider($this->config, null, $client);
        $context = $this->createMockContext();

        $result = $provider->analyze($context);

        $this->assertFalse($result->isEmpty());
        $this->assertSame('Success after retry', $result->getMessage());
    }

    public function test_it_handles_api_errors_gracefully(): void
    {
        $mock = new MockHandler([
            new ClientException('API Error', new Request('POST', '/chat/completions'), new Response(500)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $this->logger
            ->expects($this->once())
            ->method('error');

        $provider = new OpenAIProvider($this->config, $this->logger, $client);
        $context = $this->createMockContext();

        $result = $provider->analyze($context);

        $this->assertTrue($result->isEmpty());
    }

    public function test_it_handles_empty_response(): void
    {
        $mockResponse = [
            'choices' => [],
        ];

        $mock = new MockHandler([
            new Response(200, [], json_encode($mockResponse)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new OpenAIProvider($this->config, null, $client);
        $context = $this->createMockContext();

        $result = $provider->analyze($context);

        $this->assertTrue($result->isEmpty());
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

