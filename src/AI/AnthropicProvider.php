<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\AI;

use ClarityPHP\RuntimeInsight\Config;
use ClarityPHP\RuntimeInsight\Contracts\AIProviderInterface;
use ClarityPHP\RuntimeInsight\DTO\Explanation;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

use function is_array;
use function is_int;
use function is_string;
use function json_decode;
use function sleep;
use function str_contains;
use function str_starts_with;

/**
 * Anthropic Claude provider for AI-powered error analysis.
 */
final class AnthropicProvider implements AIProviderInterface
{
    private const API_BASE_URL = 'https://api.anthropic.com/v1';

    private const API_VERSION = '2023-06-01';

    private const MAX_RETRIES = 3;

    private const RETRY_DELAY = 1;

    private const DEFAULT_MAX_TOKENS = 1000;

    public function __construct(
        private readonly Config $config,
        private readonly ?LoggerInterface $logger = null,
        private readonly ?Client $httpClient = null,
    ) {}

    public function analyze(RuntimeContext $context): Explanation
    {
        if (! $this->isAvailable()) {
            return Explanation::empty();
        }

        try {
            $response = $this->makeRequest($context);

            return $this->parseResponse($response, $context);
        } catch (Throwable $e) {
            $this->logError('Anthropic API error', $e);

            return Explanation::empty();
        }
    }

    public function isAvailable(): bool
    {
        return $this->config->isAIEnabled()
            && $this->config->getAIProvider() === 'anthropic'
            && $this->config->getAIApiKey() !== null
            && $this->config->getAIApiKey() !== '';
    }

    public function getName(): string
    {
        return 'anthropic';
    }

    /**
     * Make API request to Anthropic Messages API with retry logic.
     *
     * @return array<string, mixed>
     */
    private function makeRequest(RuntimeContext $context): array
    {
        $client = $this->httpClient ?? new Client([
            'base_uri' => self::API_BASE_URL . '/',
            'timeout' => $this->config->getAITimeout(),
        ]);

        $prompt = $this->buildPrompt($context);
        $payload = [
            'model' => $this->config->getAIModel(),
            'max_tokens' => self::DEFAULT_MAX_TOKENS,
            'system' => $this->getSystemPrompt(),
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
        ];

        $retries = 0;
        $lastException = null;

        while ($retries < self::MAX_RETRIES) {
            try {
                $response = $client->post('messages', [
                    RequestOptions::HEADERS => [
                        'Content-Type' => 'application/json',
                        'anthropic-version' => self::API_VERSION,
                        'x-api-key' => $this->config->getAIApiKey(),
                    ],
                    RequestOptions::JSON => $payload,
                ]);

                $body = $response->getBody()->getContents();
                $data = json_decode($body, true);

                if (! is_array($data)) {
                    throw new RuntimeException('Invalid response from Anthropic API');
                }

                /** @var array<string, mixed> $data */
                return $data;
            } catch (GuzzleException $e) {
                $lastException = $e;

                $isRateLimit = false;
                if ($e instanceof ClientException || $e instanceof ServerException) {
                    if ($e->getResponse()->getStatusCode() === 429) {
                        $isRateLimit = true;
                    }
                } elseif ($e->getCode() === 429) {
                    $isRateLimit = true;
                }

                if ($isRateLimit) {
                    $retries++;
                    if ($retries < self::MAX_RETRIES) {
                        $delay = self::RETRY_DELAY * $retries;
                        sleep($delay);
                        continue;
                    }
                }

                throw $e;
            }
        }

        if ($lastException !== null) {
            throw $lastException;
        }

        throw new RuntimeException('Failed to get response from Anthropic API');
    }

    private function buildPrompt(RuntimeContext $context): string
    {
        $prompt = "Analyze this PHP runtime error and provide a clear explanation.\n\n";
        $prompt .= "Exception Type: {$context->exception->class}\n";
        $prompt .= "Error Message: {$context->exception->message}\n";
        $prompt .= "File: {$context->exception->file}\n";
        $prompt .= "Line: {$context->exception->line}\n\n";

        if ($context->sourceContext->codeSnippet !== '') {
            $prompt .= "Source Code Context:\n```php\n{$context->sourceContext->codeSnippet}\n```\n\n";
        }

        if ($context->stackTrace->frames !== []) {
            $prompt .= "Stack Trace (first 5 frames):\n";
            $frameCount = 0;
            foreach ($context->stackTrace->frames as $frame) {
                if ($frameCount >= 5) {
                    break;
                }
                $prompt .= "  - {$frame->file}:{$frame->line} in {$frame->class}{$frame->type}{$frame->function}\n";
                $frameCount++;
            }
            $prompt .= "\n";
        }

        if ($context->requestContext !== null) {
            $prompt .= "Request: {$context->requestContext->method} {$context->requestContext->uri}\n";
        }

        $prompt .= "\nProvide your response in JSON format with the following structure: ";
        $prompt .= '{"message": "Brief error summary", "cause": "Root cause explanation", ';
        $prompt .= '"suggestions": ["Fix 1", "Fix 2"], "confidence": 0.85}';

        return $prompt;
    }

    private function getSystemPrompt(): string
    {
        return 'You are an expert PHP developer helping to debug runtime errors. '
            . 'Analyze the provided error information and provide clear, actionable explanations. '
            . 'Focus on the root cause and provide specific fix suggestions. '
            . 'Respond only with valid JSON in the requested structure.';
    }

    /**
     * Parse Anthropic API response into Explanation.
     *
     * @param array<string, mixed> $response
     */
    private function parseResponse(array $response, RuntimeContext $context): Explanation
    {
        $content = $response['content'] ?? [];
        if (! is_array($content) || $content === []) {
            return Explanation::empty();
        }

        $text = '';
        foreach ($content as $block) {
            if (! is_array($block)) {
                continue;
            }
            $type = $block['type'] ?? null;
            if ($type === 'text' && isset($block['text']) && is_string($block['text'])) {
                $text .= $block['text'];
            }
        }

        if ($text === '') {
            return Explanation::empty();
        }

        $parsed = json_decode($text, true);

        if (! is_array($parsed)) {
            return $this->parseTextResponse($text, $context);
        }

        $suggestions = [];
        if (isset($parsed['suggestions']) && is_array($parsed['suggestions'])) {
            foreach ($parsed['suggestions'] as $suggestion) {
                if (is_string($suggestion)) {
                    $suggestions[] = $suggestion;
                }
            }
        }

        $tokensUsed = null;
        if (isset($response['usage']) && is_array($response['usage'])) {
            $usage = $response['usage'];
            $input = $usage['input_tokens'] ?? null;
            $output = $usage['output_tokens'] ?? null;
            if (is_int($input) && is_int($output)) {
                $tokensUsed = $input + $output;
            }
        }

        return new Explanation(
            message: is_string($parsed['message'] ?? null) ? $parsed['message'] : $context->exception->message,
            cause: is_string($parsed['cause'] ?? null) ? $parsed['cause'] : 'Unable to determine root cause',
            suggestions: $suggestions,
            confidence: isset($parsed['confidence']) && is_numeric($parsed['confidence']) ? (float) $parsed['confidence'] : 0.7,
            errorType: $context->exception->class,
            location: "{$context->exception->file}:{$context->exception->line}",
            metadata: [
                'provider' => 'anthropic',
                'model' => $this->config->getAIModel(),
                'tokens_used' => $tokensUsed,
            ],
        );
    }

    private function parseTextResponse(string $content, RuntimeContext $context): Explanation
    {
        $suggestions = [];
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_contains($line, ':')) {
                continue;
            }
            if (str_starts_with($line, '-') || str_starts_with($line, '*')) {
                $suggestions[] = trim($line, '-* ');
            }
        }

        return new Explanation(
            message: $context->exception->message,
            cause: $content,
            suggestions: $suggestions !== [] ? $suggestions : ['Review the error message and stack trace'],
            confidence: 0.6,
            errorType: $context->exception->class,
            location: "{$context->exception->file}:{$context->exception->line}",
        );
    }

    private function logError(string $message, Throwable $e): void
    {
        if ($this->logger !== null) {
            $this->logger->error($message, [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
