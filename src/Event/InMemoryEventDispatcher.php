<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Event;

use ClarityPHP\RuntimeInsight\Contracts\EventDispatcherInterface;

use function get_class;

/**
 * Simple in-memory event dispatcher: listeners registered per event class name.
 */
final class InMemoryEventDispatcher implements EventDispatcherInterface
{
    /**
     * @var array<string, list<callable(object): void>>
     */
    private array $listeners = [];

    /**
     * Register a listener for an event class. Called when dispatch() receives that class.
     *
     * @param callable(object): void $listener
     */
    public function addListener(string $eventClass, callable $listener): self
    {
        if (! isset($this->listeners[$eventClass])) {
            $this->listeners[$eventClass] = [];
        }
        $this->listeners[$eventClass][] = $listener;

        return $this;
    }

    public function dispatch(object $event): void
    {
        $class = get_class($event);
        if (! isset($this->listeners[$class])) {
            return;
        }
        foreach ($this->listeners[$class] as $listener) {
            $listener($event);
        }
    }
}
