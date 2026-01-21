<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\DTO;

use Throwable;

/**
 * Information about the exception that occurred.
 */
final readonly class ExceptionInfo
{
    public function __construct(
        public string $class,
        public string $message,
        public int $code,
        public string $file,
        public int $line,
        public ?string $previousClass = null,
        public ?string $previousMessage = null,
    ) {}

    public static function fromThrowable(Throwable $throwable): self
    {
        $previous = $throwable->getPrevious();

        return new self(
            class: $throwable::class,
            message: $throwable->getMessage(),
            code: $throwable->getCode(),
            file: $throwable->getFile(),
            line: $throwable->getLine(),
            previousClass: $previous?->getMessage() !== null ? $previous::class : null,
            previousMessage: $previous?->getMessage(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'class' => $this->class,
            'message' => $this->message,
            'code' => $this->code,
            'file' => $this->file,
            'line' => $this->line,
            'previous_class' => $this->previousClass,
            'previous_message' => $this->previousMessage,
        ];
    }
}

