<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Contracts;

/**
 * Dispatches runtime insight events for extensibility (listeners can hook before/after analysis).
 */
interface EventDispatcherInterface
{
    /**
     * Register a listener for an event class. Invoked when dispatch() receives that class.
     *
     * @param callable(object): void $listener
     */
    public function addListener(string $eventClass, callable $listener): void;

    /**
     * Dispatch an event to all registered listeners for that event class.
     */
    public function dispatch(object $event): void;
}
