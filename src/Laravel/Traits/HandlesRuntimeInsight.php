<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Laravel\Traits;

use ClarityPHP\RuntimeInsight\Laravel\ExceptionHandler;

/**
 * Trait to add Runtime Insight exception handling to Laravel's exception handler.
 *
 * Usage:
 * ```php
 * class Handler extends ExceptionHandler
 * {
 *     use HandlesRuntimeInsight;
 *
 *     public function report(Throwable $e): void
 *     {
 *         $this->analyzeWithRuntimeInsight($e);
 *         parent::report($e);
 *     }
 * }
 * ```
 *
 * @phpstan-ignore-next-line trait.unused
 */
trait HandlesRuntimeInsight
{
    /**
     * Analyze exception with Runtime Insight.
     *
     * Call this method from your exception handler's report() method.
     */
    protected function analyzeWithRuntimeInsight(\Throwable $exception): void
    {
        try {
            /** @var ExceptionHandler $handler */
            $handler = app(ExceptionHandler::class);
            $handler->handle($exception);
        } catch (\Throwable) {
            // Silently fail - don't let Runtime Insight break exception handling
        }
    }
}

