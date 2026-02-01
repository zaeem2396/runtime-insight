<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\AI;

use ClarityPHP\RuntimeInsight\Config;
use ClarityPHP\RuntimeInsight\Contracts\AIProviderInterface;
use ClarityPHP\RuntimeInsight\DTO\Explanation;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;
use Throwable;

use function is_array;
use function is_int;
use function is_string;
use function json_decode;
use function rtrim;
use function str_contains;
use function str_starts_with;

/**
 * Ollama provider for local AI-powered error analysis.
 *
 * Uses the Ollama /api/chat endpoint. No API key required.
 */
final class OllamaProvider implements AIProviderInterface
{
    private const DEFAULT_BASE_URL = 'http://localhost:11434';

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
            $this->logError('Ollama API error', $e);

            return Explanation::empty();
        }
    }

    public function isAvailable(): bool
    {
        return $this->config->isAIConfigured()
            && $this->config->getAIProvider() === 'ollama';
    }

    public function getName(): string
    {
        return 'ollama';
    }

    private function getBaseUrl(): string
    {
        $url = $this->config->getAIBaseUrl();

        return $url !== null && $url !== '' ? rtrim($url, '/') : self::DEFAULT_BASE_URL;
    }

    /**
     * Make API request to Ollama /api/chat.
     *
     * @return array<string, mixed>
     */
    private function makeRequest(RuntimeContext $context): array
    {
        $baseUrl = $this->getBaseUrl();
        $client = $this->httpClient ?? new Client([
            'base_uri' => $baseUrl . '/',
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
            'stream' => false,
            'format' => 'json',
        ];

        $response = $client->post('api/chat', [
            RequestOptions::JSON => $payload,
        ]);

        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);

        if (! is_array($data)) {
            return [];
        }

        /** @var array<string, mixed> $data */
        return $data;
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

        $prompt .= "\nRespond with valid JSON only: ";
        $prompt .= '{"message": "Brief error summary", "cause": "Root cause explanation", ';
        $prompt .= '"suggestions": ["Fix 1", "Fix 2"], "confidence": 0.85}';

        return $prompt;
    }

    private function getSystemPrompt(): string
    {
        return 'You are an expert PHP developer helping to debug runtime errors. '
            . 'Analyze the provided error information and respond only with valid JSON in this exact structure: '
            . '{"message": "string", "cause": "string", "suggestions": ["string"], "confidence": number}.';
    }

    /**
     * Parse Ollama API response into Explanation.
     *
     * @param array<string, mixed> $response
     */
    private function parseResponse(array $response, RuntimeContext $context): Explanation
    {
        $message = $response['message'] ?? null;
        if (! is_array($message) || ! isset($message['content']) || ! is_string($message['content'])) {
            return Explanation::empty();
        }

        $content = $message['content'];
        if ($content === '') {
            return Explanation::empty();
        }

        $parsed = json_decode($content, true);

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

        $evalCount = null;
        if (isset($response['eval_count']) && is_int($response['eval_count'])) {
            $evalCount = $response['eval_count'];
        }
        $promptEvalCount = null;
        if (isset($response['prompt_eval_count']) && is_int($response['prompt_eval_count'])) {
            $promptEvalCount = $response['prompt_eval_count'];
        }

        return new Explanation(
            message: is_string($parsed['message'] ?? null) ? $parsed['message'] : $context->exception->message,
            cause: is_string($parsed['cause'] ?? null) ? $parsed['cause'] : 'Unable to determine root cause',
            suggestions: $suggestions,
            confidence: isset($parsed['confidence']) && is_numeric($parsed['confidence']) ? (float) $parsed['confidence'] : 0.7,
            errorType: $context->exception->class,
            location: "{$context->exception->file}:{$context->exception->line}",
            metadata: [
                'provider' => 'ollama',
                'model' => $this->config->getAIModel(),
                'eval_count' => $evalCount,
                'prompt_eval_count' => $promptEvalCount,
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
