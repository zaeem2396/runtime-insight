<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Laravel\Context;

use ClarityPHP\RuntimeInsight\Config;
use ClarityPHP\RuntimeInsight\Context\ContextBuilder;
use ClarityPHP\RuntimeInsight\Contracts\ContextBuilderInterface;
use ClarityPHP\RuntimeInsight\DTO\ApplicationContext;
use ClarityPHP\RuntimeInsight\DTO\DatabaseContext;
use ClarityPHP\RuntimeInsight\DTO\RequestContext;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route as RouteFacade;
use Throwable;

use function in_array;
use function is_array;
use function is_string;

/**
 * Laravel-specific context builder that extends base ContextBuilder
 * with Laravel request, route, and application context.
 */
final class LaravelContextBuilder implements ContextBuilderInterface
{
    public function __construct(
        private readonly ContextBuilder $baseBuilder,
        private readonly Application $app,
        private readonly Config $config,
    ) {}

    /**
     * Build a runtime context from a throwable with Laravel-specific information.
     */
    public function build(Throwable $throwable): RuntimeContext
    {
        $baseContext = $this->baseBuilder->build($throwable);

        return new RuntimeContext(
            exception: $baseContext->exception,
            stackTrace: $baseContext->stackTrace,
            sourceContext: $baseContext->sourceContext,
            requestContext: $this->config->shouldIncludeRequest()
                ? $this->buildRequestContext()
                : null,
            applicationContext: $this->buildApplicationContext(),
            databaseContext: $this->buildDatabaseContext(),
        );
    }

    /**
     * Build database/query context from Laravel query log (when enabled).
     */
    private function buildDatabaseContext(): ?DatabaseContext
    {
        if (! $this->config->includeDatabaseQueries()) {
            return null;
        }

        try {
            /** @var array<int, array<string, mixed>> $log */
            $log = DB::getQueryLog();
            if ($log === []) {
                return null;
            }

            $max = $this->config->getMaxDatabaseQueries();
            $recent = array_slice($log, -$max);
            $queries = [];

            foreach ($recent as $entry) {
                /** @var array<string, mixed> $entry */
                $query = isset($entry['query']) && is_string($entry['query']) ? $entry['query'] : '';
                $time = isset($entry['time']) && is_numeric($entry['time']) ? (float) $entry['time'] : null;
                if ($query !== '') {
                    $queries[] = $time !== null ? sprintf('%s [%.2fms]', $query, $time) : $query;
                }
            }

            return $queries === [] ? null : new DatabaseContext(recentQueries: $queries);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Build request context from Laravel request.
     */
    private function buildRequestContext(): ?RequestContext
    {
        try {
            /** @var mixed $request */
            $request = $this->app->make('request');

            if (! $request instanceof Request) {
                return null;
            }

            /** @var array<string, mixed> $allData */
            $allData = $request->all();
            /** @var array<string, mixed> $queryData */
            $queryData = $request->query();

            $sanitizedBody = $this->sanitizeInputs($allData);
            $sanitizedQuery = $this->sanitizeInputs($queryData);

            /** @var array<string, array<int, string>|string> $headers */
            $headers = $request->headers->all();

            return new RequestContext(
                method: $request->method(),
                uri: $request->fullUrl(),
                headers: $this->sanitizeHeaders($headers),
                query: $sanitizedQuery,
                body: $sanitizedBody,
                clientIp: $request->ip(),
                userAgent: $request->userAgent(),
            );
        } catch (Throwable) {
            // If request is not available (e.g., in console), return null
            return null;
        }
    }

    /**
     * Build application context from Laravel application state.
     */
    private function buildApplicationContext(): ApplicationContext
    {
        try {
            /** @var string|bool $env */
            $env = $this->app->environment();
            $environment = is_string($env) ? $env : 'unknown';
            $route = null;
            $controller = null;
            $action = null;
            $userId = null;

            // Get route information
            try {
                /** @var Route|null $currentRoute */
                $currentRoute = RouteFacade::current();

                if ($currentRoute !== null) {
                    $route = $currentRoute->getName() ?? $currentRoute->uri();
                    $actionName = $currentRoute->getActionName();
                    /** @phpstan-ignore-next-line */
                    $action = is_string($actionName) ? $actionName : null;

                    // Extract controller from action
                    /** @phpstan-ignore-next-line */
                    if ($action !== null) {
                        if (str_contains($action, '@')) {
                            /** @var array{0: string, 1?: string} $parts */
                            $parts = explode('@', $action, 2);
                            $controller = $parts[0];
                        } elseif (str_contains($action, '::')) {
                            /** @var array{0: string, 1?: string} $parts */
                            $parts = explode('::', $action, 2);
                            $controller = $parts[0];
                        } else {
                            $controller = $action;
                        }
                    }
                }
            } catch (Throwable) {
                // Route information not available
            }

            // Get authenticated user ID
            try {
                if (Auth::check()) {
                    $user = Auth::user();
                    if ($user !== null) {
                        $identifier = $user->getAuthIdentifier();
                        if ($identifier !== null) {
                            /** @var int|string $identifier */
                            $userId = (string) $identifier;
                        }
                    }
                }
            } catch (Throwable) {
                // Auth not available
            }

            // Get Laravel version
            $frameworkVersion = $this->app->version();

            return new ApplicationContext(
                environment: $environment,
                route: $route,
                controller: $controller,
                action: $action,
                userId: $userId,
                framework: 'Laravel',
                frameworkVersion: $frameworkVersion,
                extra: [],
            );
        } catch (Throwable) {
            // Fallback to basic context
            /** @var string|bool $env */
            $env = $this->app->environment();
            $environment = is_string($env) ? $env : 'unknown';

            return new ApplicationContext(
                environment: $environment,
                framework: 'Laravel',
                frameworkVersion: $this->app->version(),
            );
        }
    }

    /**
     * Sanitize input data by redacting sensitive fields.
     *
     * @param array<string|int, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function sanitizeInputs(array $data): array
    {
        if (! $this->config->shouldSanitizeInputs()) {
            /** @var array<string, mixed> */
            return $data;
        }

        $redactFields = $this->config->getRedactFields();
        $sanitized = [];

        foreach ($data as $key => $value) {
            $stringKey = (string) $key;
            $lowerKey = strtolower($stringKey);

            // Check if this field should be redacted
            $shouldRedact = false;
            foreach ($redactFields as $redactField) {
                if (str_contains($lowerKey, strtolower($redactField))) {
                    $shouldRedact = true;
                    break;
                }
            }

            if ($shouldRedact) {
                $sanitized[$stringKey] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$stringKey] = $this->sanitizeInputs($value);
            } else {
                $sanitized[$stringKey] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize headers by removing sensitive information.
     *
     * @param array<string, array<int, string>|string> $headers
     *
     * @return array<string, mixed>
     */
    private function sanitizeHeaders(array $headers): array
    {
        if (! $this->config->shouldSanitizeInputs()) {
            return $headers;
        }

        $sanitized = [];
        $sensitiveHeaders = ['authorization', 'cookie', 'x-api-key', 'x-auth-token'];

        foreach ($headers as $key => $value) {
            $lowerKey = strtolower((string) $key);

            if (in_array($lowerKey, $sensitiveHeaders, true)) {
                // Preserve array structure if it's an array
                if (is_array($value)) {
                    $sanitized[$key] = ['[REDACTED]'];
                } else {
                    $sanitized[$key] = '[REDACTED]';
                }
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
