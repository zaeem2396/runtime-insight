<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\DTO;

/**
 * Structured stack trace information.
 */
final readonly class StackTraceInfo
{
    /**
     * @param array<StackFrame> $frames
     */
    public function __construct(
        public array $frames,
        public string $rawTrace = '',
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'frames' => array_map(
                static fn(StackFrame $frame): array => $frame->toArray(),
                $this->frames,
            ),
            'raw_trace' => $this->rawTrace,
        ];
    }

    public function getTopFrame(): ?StackFrame
    {
        return $this->frames[0] ?? null;
    }

    /**
     * Get frames from application code (excluding vendor).
     *
     * @return array<StackFrame>
     */
    public function getApplicationFrames(): array
    {
        return array_filter(
            $this->frames,
            static fn(StackFrame $frame): bool => ! $frame->isVendor,
        );
    }
}
