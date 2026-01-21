<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\DTO;

/**
 * Source code context around the error.
 */
final readonly class SourceContext
{
    /**
     * @param array<int, string> $lines
     */
    public function __construct(
        public string $file,
        public int $errorLine,
        public array $lines,
        public string $codeSnippet,
        public ?string $methodSignature = null,
        public ?string $className = null,
    ) {}

    public static function empty(): self
    {
        return new self(
            file: '',
            errorLine: 0,
            lines: [],
            codeSnippet: '',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'file' => $this->file,
            'error_line' => $this->errorLine,
            'lines' => $this->lines,
            'code_snippet' => $this->codeSnippet,
            'method_signature' => $this->methodSignature,
            'class_name' => $this->className,
        ];
    }

    public function isEmpty(): bool
    {
        return $this->file === '' || $this->lines === [];
    }
}

