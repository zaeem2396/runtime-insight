<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\DTO;

/**
 * Application-level context.
 */
final readonly class ApplicationContext
{
    /**
     * @param array<string, mixed> $extra
     */
    public function __construct(
        public string $environment,
        public ?string $route = null,
        public ?string $controller = null,
        public ?string $action = null,
        public ?string $userId = null,
        public ?string $framework = null,
        public ?string $frameworkVersion = null,
        public array $extra = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'environment' => $this->environment,
            'route' => $this->route,
            'controller' => $this->controller,
            'action' => $this->action,
            'user_id' => $this->userId,
            'framework' => $this->framework,
            'framework_version' => $this->frameworkVersion,
            'extra' => $this->extra,
        ];
    }
}

