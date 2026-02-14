<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\DTO;

use function is_array;
use function is_string;

/**
 * Represents an explanation for a runtime error.
 */
final readonly class Explanation
{
    /**
     * @param array<string> $suggestions
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private string $message,
        private string $cause,
        private array $suggestions = [],
        private float $confidence = 0.0,
        private ?string $errorType = null,
        private ?string $location = null,
        private array $metadata = [],
        private ?string $codeSnippet = null,
        private ?string $callSiteLocation = null,
    ) {}

    /**
     * Create an empty explanation (when analysis is disabled).
     */
    public static function empty(): self
    {
        return new self(
            message: '',
            cause: '',
            suggestions: [],
            confidence: 0.0,
        );
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getCause(): string
    {
        return $this->cause;
    }

    /**
     * @return array<string>
     */
    public function getSuggestions(): array
    {
        return $this->suggestions;
    }

    public function getConfidence(): float
    {
        return $this->confidence;
    }

    public function getErrorType(): ?string
    {
        return $this->errorType;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    /**
     * Code snippet around the error line (with line numbers). Shown as "Code block to update".
     */
    public function getCodeSnippet(): ?string
    {
        return $this->codeSnippet;
    }

    /**
     * Call site where the error was triggered (e.g. "file.php:145"). Shown as "Called from".
     */
    public function getCallSiteLocation(): ?string
    {
        return $this->callSiteLocation;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function isEmpty(): bool
    {
        return $this->message === '' && $this->cause === '';
    }

    /**
     * Return a copy with code context attached (snippet and optional call site).
     * Used by the engine to show which code block to update.
     */
    public function withCodeContext(string $codeSnippet, ?string $callSiteLocation = null): self
    {
        return new self(
            message: $this->message,
            cause: $this->cause,
            suggestions: $this->suggestions,
            confidence: $this->confidence,
            errorType: $this->errorType,
            location: $this->location,
            metadata: $this->metadata,
            codeSnippet: $codeSnippet !== '' ? $codeSnippet : null,
            callSiteLocation: $callSiteLocation,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'cause' => $this->cause,
            'suggestions' => $this->suggestions,
            'confidence' => $this->confidence,
            'error_type' => $this->errorType,
            'location' => $this->location,
            'metadata' => $this->metadata,
            'code_snippet' => $this->codeSnippet,
            'call_site_location' => $this->callSiteLocation,
        ];
    }

    /**
     * Create an Explanation from an array (e.g. from cache).
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $suggestions = [];
        if (isset($data['suggestions']) && is_array($data['suggestions'])) {
            foreach ($data['suggestions'] as $s) {
                if (is_string($s)) {
                    $suggestions[] = $s;
                }
            }
        }

        $metadata = [];
        if (isset($data['metadata']) && is_array($data['metadata'])) {
            foreach ($data['metadata'] as $k => $v) {
                if (is_string($k)) {
                    $metadata[$k] = $v;
                }
            }
        }

        return new self(
            message: is_string($data['message'] ?? null) ? $data['message'] : '',
            cause: is_string($data['cause'] ?? null) ? $data['cause'] : '',
            suggestions: $suggestions,
            confidence: isset($data['confidence']) && is_numeric($data['confidence']) ? (float) $data['confidence'] : 0.0,
            errorType: is_string($data['error_type'] ?? null) ? $data['error_type'] : null,
            location: is_string($data['location'] ?? null) ? $data['location'] : null,
            metadata: $metadata,
            codeSnippet: is_string($data['code_snippet'] ?? null) ? $data['code_snippet'] : null,
            callSiteLocation: is_string($data['call_site_location'] ?? null) ? $data['call_site_location'] : null,
        );
    }
}
