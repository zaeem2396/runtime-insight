<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\DTO;

/**
 * Single frame in a stack trace.
 */
final readonly class StackFrame
{
    /**
     * @param array<mixed> $args
     */
    public function __construct(
        public ?string $file,
        public ?int $line,
        public ?string $class,
        public ?string $function,
        public ?string $type,
        public array $args = [],
        public bool $isVendor = false,
    ) {}

    /**
     * Create from PHP stack trace array element.
     *
     * @param array<string, mixed> $frame
     */
    public static function fromArray(array $frame, bool $isVendor = false): self
    {
        return new self(
            file: $frame['file'] ?? null,
            line: $frame['line'] ?? null,
            class: $frame['class'] ?? null,
            function: $frame['function'] ?? null,
            type: $frame['type'] ?? null,
            args: $frame['args'] ?? [],
            isVendor: $isVendor,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'file' => $this->file,
            'line' => $this->line,
            'class' => $this->class,
            'function' => $this->function,
            'type' => $this->type,
            'is_vendor' => $this->isVendor,
        ];
    }

    public function getFullMethod(): string
    {
        if ($this->class === null) {
            return $this->function ?? '';
        }

        return $this->class . ($this->type ?? '::') . $this->function;
    }

    public function getLocation(): string
    {
        if ($this->file === null) {
            return '';
        }

        return $this->file . ':' . ($this->line ?? '?');
    }
}

