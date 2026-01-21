<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight;

/**
 * Configuration container for Runtime Insight.
 */
final readonly class Config
{
    /**
     * @param array<string> $environments
     * @param array<string> $disabledEnvironments
     * @param array<string> $redactFields
     */
    public function __construct(
        private bool $enabled = true,
        private bool $aiEnabled = true,
        private string $aiProvider = 'openai',
        private string $aiModel = 'gpt-4.1-mini',
        private ?string $aiApiKey = null,
        private int $aiTimeout = 5,
        private int $sourceLines = 10,
        private bool $includeRequest = true,
        private bool $sanitizeInputs = true,
        private array $environments = ['local', 'staging'],
        private array $disabledEnvironments = ['production'],
        private array $redactFields = ['password', 'token', 'secret', 'api_key'],
        private ?string $currentEnvironment = null,
    ) {}

    public static function fromArray(array $config): self
    {
        return new self(
            enabled: $config['enabled'] ?? true,
            aiEnabled: $config['ai']['enabled'] ?? true,
            aiProvider: $config['ai']['provider'] ?? 'openai',
            aiModel: $config['ai']['model'] ?? 'gpt-4.1-mini',
            aiApiKey: $config['ai']['api_key'] ?? null,
            aiTimeout: $config['ai']['timeout'] ?? 5,
            sourceLines: $config['context']['source_lines'] ?? 10,
            includeRequest: $config['context']['include_request'] ?? true,
            sanitizeInputs: $config['context']['sanitize_inputs'] ?? true,
            environments: $config['environments'] ?? ['local', 'staging'],
            disabledEnvironments: $config['disabled_environments'] ?? ['production'],
            redactFields: $config['context']['redact_fields'] ?? ['password', 'token', 'secret', 'api_key'],
            currentEnvironment: $config['current_environment'] ?? null,
        );
    }

    public function isEnabled(): bool
    {
        if (! $this->enabled) {
            return false;
        }

        if ($this->currentEnvironment === null) {
            return true;
        }

        if (\in_array($this->currentEnvironment, $this->disabledEnvironments, true)) {
            return false;
        }

        return \in_array($this->currentEnvironment, $this->environments, true);
    }

    public function isAIEnabled(): bool
    {
        return $this->aiEnabled && $this->aiApiKey !== null;
    }

    public function getAIProvider(): string
    {
        return $this->aiProvider;
    }

    public function getAIModel(): string
    {
        return $this->aiModel;
    }

    public function getAIApiKey(): ?string
    {
        return $this->aiApiKey;
    }

    public function getAITimeout(): int
    {
        return $this->aiTimeout;
    }

    public function getSourceLines(): int
    {
        return $this->sourceLines;
    }

    public function shouldIncludeRequest(): bool
    {
        return $this->includeRequest;
    }

    public function shouldSanitizeInputs(): bool
    {
        return $this->sanitizeInputs;
    }

    /**
     * @return array<string>
     */
    public function getRedactFields(): array
    {
        return $this->redactFields;
    }
}

