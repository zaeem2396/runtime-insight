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
use Throwable;

use function json_decode;
use function json_encode;
use function sleep;
use function str_contains;

/**
 * OpenAI provider for AI-powered error analysis.
 */
final class OpenAIProvider implements AIProviderInterface
{
    private const API_BASE_URL = 'https://api.openai.com/v1';
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY = 1; // seconds

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
            $this->logError('OpenAI API error', $e);

            return Explanation::empty();
        }
    }

    public function isAvailable(): bool
    {
        return $this->config->isAIEnabled()
            && $this->config->getAIProvider() === 'openai'
            && $this->config->getAIApiKey() !== null
            && $this->config->getAIApiKey() !== '';
    }

    public function getName(): string
    {
        return 'openai';
    }

    /**
     * Make API request to OpenAI with retry logic.
     *
     * @return array<string, mixed>
     */
    private function makeRequest(RuntimeContext $context): array
    {
        $client = $this->httpClient ?? new Client([
            'base_uri' => self::API_BASE_URL,
            'timeout' => $this->config->getAITimeout(),
        ]);

        $prompt = $this->buildPrompt($context);
        $payload = [
            'model' => $this->config->getAIModel(),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $this->getSystemPrompt(),
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'max_tokens' => 1000,
            'temperature' => 0.3,
        ];

        $retries = 0;
        $lastException = null;

        while ($retries < self::MAX_RETRIES) {
            try {
                $response = $client->post('/chat/completions', [
                    RequestOptions::HEADERS => [
                        'Authorization' => 'Bearer ' . $this->config->getAIApiKey(),
                        'Content-Type' => 'application/json',
                    ],
                    RequestOptions::JSON => $payload,
                ]);

                $body = $response->getBody()->getContents();
                $data = json_decode($body, true);

                if (! is_array($data)) {
                    throw new \RuntimeException('Invalid response from OpenAI API');
                }

                /** @var array<string, mixed> $data */
                return $data;
            } catch (GuzzleException $e) {
                $lastException = $e;

                // Check if it's a rate limit error (429)
                $isRateLimit = false;
                if ($e instanceof ClientException || $e instanceof ServerException) {
                    $statusCode = $e->getResponse()->getStatusCode();
                    $isRateLimit = $statusCode === 429;
                } elseif ($e->getCode() === 429) {
                    $isRateLimit = true;
                }

                if ($isRateLimit) {
                    $retries++;
                    if ($retries < self::MAX_RETRIES) {
                        $delay = self::RETRY_DELAY * $retries; // Exponential backoff
                        sleep($delay);
                        continue;
                    }
                }

                // For other errors, don't retry
                throw $e;
            }
        }

        // If we exhausted retries, throw the last exception
        if ($lastException !== null) {
            throw $lastException;
        }

        throw new \RuntimeException('Failed to get response from OpenAI API');
    }

    /**
     * Build the prompt for AI analysis.
     */
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

        $prompt .= "\nProvide:\n";
        $prompt .= "1. A clear explanation of why this error occurred\n";
        $prompt .= "2. The root cause\n";
        $prompt .= "3. Specific, actionable suggestions to fix it\n";
        $prompt .= "4. A confidence score (0.0 to 1.0)\n";

        return $prompt;
    }

    /**
     * Get the system prompt for OpenAI.
     */
    private function getSystemPrompt(): string
    {
        return 'You are an expert PHP developer helping to debug runtime errors. '
            . 'Analyze the provided error information and provide clear, actionable explanations. '
            . 'Focus on the root cause and provide specific fix suggestions. '
            . 'Your response should be in JSON format with the following structure: '
            . '{"message": "Brief error summary", "cause": "Root cause explanation", '
            . '"suggestions": ["Fix 1", "Fix 2"], "confidence": 0.85}';
    }

    /**
     * Parse OpenAI API response into Explanation.
     *
     * @param array<string, mixed> $response
     */
    private function parseResponse(array $response, RuntimeContext $context): Explanation
    {
        $choices = $response['choices'] ?? [];
        if (! is_array($choices) || $choices === [] || ! isset($choices[0])) {
            return Explanation::empty();
        }

        $firstChoice = $choices[0];
        if (! is_array($firstChoice) || ! isset($firstChoice['message']) || ! is_array($firstChoice['message'])) {
            return Explanation::empty();
        }

        $message = $firstChoice['message'];
        if (! isset($message['content']) || ! is_string($message['content'])) {
            return Explanation::empty();
        }

        $content = $message['content'];
        $parsed = json_decode($content, true);

        // If response is not JSON, try to extract information
        if (! is_array($parsed)) {
            return $this->parseTextResponse($content, $context);
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
        if (isset($response['usage']) && is_array($response['usage']) && isset($response['usage']['total_tokens'])) {
            $tokensUsed = is_int($response['usage']['total_tokens']) ? $response['usage']['total_tokens'] : null;
        }

        return new Explanation(
            message: is_string($parsed['message'] ?? null) ? $parsed['message'] : $context->exception->message,
            cause: is_string($parsed['cause'] ?? null) ? $parsed['cause'] : 'Unable to determine root cause',
            suggestions: $suggestions,
            confidence: isset($parsed['confidence']) && is_numeric($parsed['confidence']) ? (float) $parsed['confidence'] : 0.7,
            errorType: $context->exception->class,
            location: "{$context->exception->file}:{$context->exception->line}",
            metadata: [
                'provider' => 'openai',
                'model' => $this->config->getAIModel(),
                'tokens_used' => $tokensUsed,
            ],
        );
    }

    /**
     * Parse a text response when JSON parsing fails.
     */
    private function parseTextResponse(string $content, RuntimeContext $context): Explanation
    {
        // Try to extract structured information from text
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

    /**
     * Log an error.
     */
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

