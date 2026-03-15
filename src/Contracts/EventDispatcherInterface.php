<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Contracts;

/**
 * Dispatches runtime insight events for extensibility (listeners can hook before/after analysis).
 */
interface EventDispatcherInterface
{
    /**
     * Dispatch an event to all registered listeners for that event class.
     */
    public function dispatch(object $event): void;
}
