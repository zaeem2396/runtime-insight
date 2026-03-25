<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Event;

use ClarityPHP\RuntimeInsight\Config;
use Psr\Log\LoggerInterface;

/**
 * Builds {@see InMemoryEventDispatcher} with optional framework integrations (e.g. webhooks).
 */
final class InMemoryEventDispatcherFactory
{
    public static function create(Config $config, ?LoggerInterface $logger = null): InMemoryEventDispatcher
    {
        return new InMemoryEventDispatcher();
    }
}
