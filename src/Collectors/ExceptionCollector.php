<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Collectors;

use ClarityPHP\RuntimeInsight\Contracts\SignalCollectorInterface;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;

/**
 * Collects exception summary for pipeline (class, message, location).
 * Exception data is already in RuntimeContext; this exposes it as a signal.
 */
final class ExceptionCollector implements SignalCollectorInterface
{
    public function getName(): string
    {
        return 'exception';
    }

    public function isEnabled(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function collect(RuntimeContext $context): array
    {
        $e = $context->exception;

        return [
            'class' => $e->class,
            'message' => $e->message,
            'file' => $e->file,
            'line' => $e->line,
            'code' => $e->code,
        ];
    }
}
