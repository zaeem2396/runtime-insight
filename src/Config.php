<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight;

use function in_array;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;

/**
 * Configuration container for Runtime Insight.
 */
final readonly class Config
{
    /**
     * @param array<string> $environments
     * @param array<string> $disabledEnvironments
     * @param array<string> $redactFields
     * @param array<string> $aiFallback
     */
    public function __construct(
        private bool $enabled = true,
        private bool $aiEnabled = true,
        private string $aiProvider = 'openai',
        private string $aiModel = 'gpt-4.1-mini',
        private ?string $aiApiKey = null,
        private ?string $aiBaseUrl = null,
        private int $aiTimeout = 5,
        private array $aiFallback = [],
        private int $sourceLines = 10,
        private bool $includeRequest = true,
        private bool $sanitizeInputs = true,
        private array $environments = ['local', 'staging'],
        private array $disabledEnvironments = ['production'],
        private array $redactFields = ['password', 'token', 'secret', 'api_key'],
        private ?string $currentEnvironment = null,
    ) {}

    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        $ai = is_array($config['ai'] ?? null) ? $config['ai'] : [];
        $context = is_array($config['context'] ?? null) ? $config['context'] : [];

        $enabled = $config['enabled'] ?? true;
        $aiEnabled = $ai['enabled'] ?? true;
        $aiProvider = $ai['provider'] ?? 'openai';
        $aiModel = $ai['model'] ?? 'gpt-4.1-mini';
        $aiApiKey = $ai['api_key'] ?? null;
        $aiBaseUrl = $ai['base_url'] ?? null;
        $aiTimeout = $ai['timeout'] ?? 5;
        $aiFallback = $ai['fallback'] ?? [];
        $sourceLines = $context['source_lines'] ?? 10;
        $includeRequest = $context['include_request'] ?? true;
        $sanitizeInputs = $context['sanitize_inputs'] ?? true;
        $environments = $config['environments'] ?? ['local', 'staging'];
        $disabledEnvironments = $config['disabled_environments'] ?? ['production'];
        $redactFields = $context['redact_fields'] ?? ['password', 'token', 'secret', 'api_key'];
        $currentEnvironment = $config['current_environment'] ?? null;

        return new self(
            enabled: is_bool($enabled) ? $enabled : true,
            aiEnabled: is_bool($aiEnabled) ? $aiEnabled : true,
            aiProvider: is_string($aiProvider) ? $aiProvider : 'openai',
            aiModel: is_string($aiModel) ? $aiModel : 'gpt-4.1-mini',
            aiApiKey: is_string($aiApiKey) ? $aiApiKey : null,
            aiBaseUrl: is_string($aiBaseUrl) ? $aiBaseUrl : null,
            aiTimeout: is_int($aiTimeout) ? $aiTimeout : 5,
            aiFallback: self::filterStringArray($aiFallback),
            sourceLines: is_int($sourceLines) ? $sourceLines : 10,
            includeRequest: is_bool($includeRequest) ? $includeRequest : true,
            sanitizeInputs: is_bool($sanitizeInputs) ? $sanitizeInputs : true,
            environments: self::filterStringArray($environments),
            disabledEnvironments: self::filterStringArray($disabledEnvironments),
            redactFields: self::filterStringArray($redactFields),
            currentEnvironment: is_string($currentEnvironment) ? $currentEnvironment : null,
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

        if (in_array($this->currentEnvironment, $this->disabledEnvironments, true)) {
            return false;
        }

        return in_array($this->currentEnvironment, $this->environments, true);
    }

    public function isAIEnabled(): bool
    {
        return $this->aiEnabled && $this->aiApiKey !== null;
    }

    /**
     * Check if AI is configured to be enabled (regardless of API key).
     */
    public function isAIConfigured(): bool
    {
        return $this->aiEnabled;
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

    public function getAIBaseUrl(): ?string
    {
        return $this->aiBaseUrl;
    }

    public function getAITimeout(): int
    {
        return $this->aiTimeout;
    }

    /**
     * Fallback provider names to try if the primary provider fails.
     *
     * @return array<string>
     */
    public function getAIFallback(): array
    {
        return $this->aiFallback;
    }

    /**
     * Return a new Config with the given AI provider (for building fallback chain).
     */
    public function withProvider(string $provider): self
    {
        return new self(
            enabled: $this->enabled,
            aiEnabled: $this->aiEnabled,
            aiProvider: $provider,
            aiModel: $this->aiModel,
            aiApiKey: $this->aiApiKey,
            aiBaseUrl: $this->aiBaseUrl,
            aiTimeout: $this->aiTimeout,
            aiFallback: $this->aiFallback,
            sourceLines: $this->sourceLines,
            includeRequest: $this->includeRequest,
            sanitizeInputs: $this->sanitizeInputs,
            environments: $this->environments,
            disabledEnvironments: $this->disabledEnvironments,
            redactFields: $this->redactFields,
            currentEnvironment: $this->currentEnvironment,
        );
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

    /**
     * Filter and ensure array contains only strings.
     *
     * @param mixed $value
     *
     * @return array<string>
     */
    private static function filterStringArray(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $item) {
            if (is_string($item)) {
                $result[] = $item;
            }
        }

        return $result;
    }
}
