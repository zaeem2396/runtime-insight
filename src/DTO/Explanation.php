<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\DTO;

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
        ];
    }
}

