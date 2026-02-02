<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Symfony\Context;

use ClarityPHP\RuntimeInsight\Config;
use ClarityPHP\RuntimeInsight\Context\ContextBuilder;
use ClarityPHP\RuntimeInsight\Contracts\ContextBuilderInterface;
use ClarityPHP\RuntimeInsight\DTO\ApplicationContext;
use ClarityPHP\RuntimeInsight\DTO\RequestContext;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;
// Security component is optional
use Throwable;

use function in_array;
use function is_array;
use function is_float;
use function is_int;
use function is_object;
use function is_string;

/**
 * Symfony-specific context builder that extends base ContextBuilder
 * with Symfony request, route, and application context.
 */
final class SymfonyContextBuilder implements ContextBuilderInterface
{
    public function __construct(
        private readonly ContextBuilder $baseBuilder,
        private readonly KernelInterface $kernel,
        private readonly ?RequestStack $requestStack,
        private readonly ?RouterInterface $router,
        private readonly mixed $tokenStorage,
        private readonly Config $config,
    ) {}

    /**
     * Build a runtime context from a throwable with Symfony-specific information.
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
            databaseContext: $baseContext->databaseContext,
            performanceContext: $baseContext->performanceContext,
        );
    }

    /**
     * Build request context from Symfony request.
     */
    private function buildRequestContext(): ?RequestContext
    {
        try {
            $request = $this->requestStack?->getCurrentRequest();

            if ($request === null) {
                return null;
            }

            $sanitizedBody = $this->sanitizeInputs($request->request->all());
            $sanitizedQuery = $this->sanitizeInputs($request->query->all());

            /** @var array<string, array<int, string>|string> $headers */
            $headers = $request->headers->all();

            return new RequestContext(
                method: $request->getMethod(),
                uri: $request->getUri(),
                headers: $this->sanitizeHeaders($headers),
                query: $sanitizedQuery,
                body: $sanitizedBody,
                clientIp: $request->getClientIp(),
                userAgent: $request->headers->get('User-Agent'),
            );
        } catch (Throwable) {
            // If request is not available (e.g., in console), return null
            return null;
        }
    }

    /**
     * Build application context from Symfony application state.
     */
    private function buildApplicationContext(): ApplicationContext
    {
        try {
            $environment = $this->kernel->getEnvironment();
            $route = null;
            $controller = null;
            $action = null;
            $userId = null;

            // Get route information
            try {
                $request = $this->requestStack?->getCurrentRequest();

                if ($request !== null && $this->router !== null) {
                    $routeName = $request->attributes->get('_route');
                    $route = is_string($routeName) ? $routeName : null;

                    $controllerInfo = $request->attributes->get('_controller');
                    if (is_string($controllerInfo)) {
                        $action = $controllerInfo;

                        // Extract controller from action string
                        if (str_contains($controllerInfo, '::')) {
                            $parts = explode('::', $controllerInfo, 2);
                            // explode with limit always returns at least one element
                            $controller = $parts[0] !== '' ? $parts[0] : null;
                        } elseif (str_contains($controllerInfo, ':')) {
                            $parts = explode(':', $controllerInfo, 2);
                            // explode with limit always returns at least one element
                            $controller = $parts[0] !== '' ? $parts[0] : null;
                        } else {
                            $controller = $controllerInfo;
                        }
                    }
                }
            } catch (Throwable) {
                // Route information not available
            }

            // Get authenticated user ID (if Security component is available)
            try {
                if ($this->tokenStorage !== null && is_object($this->tokenStorage) && method_exists($this->tokenStorage, 'getToken')) {
                    /** @phpstan-ignore-next-line */
                    $token = $this->tokenStorage->getToken();
                    if ($token !== null && is_object($token) && method_exists($token, 'getUser')) {
                        /** @phpstan-ignore-next-line */
                        $user = $token->getUser();
                        if (is_object($user) && method_exists($user, 'getId')) {
                            /** @phpstan-ignore-next-line */
                            $identifier = $user->getId();
                            if ($identifier !== null) {
                                if (is_string($identifier)) {
                                    $userId = $identifier;
                                } elseif (is_int($identifier) || is_float($identifier)) {
                                    $userId = (string) $identifier;
                                }
                            }
                        } elseif (is_string($user)) {
                            $userId = $user;
                        }
                    }
                }
            } catch (Throwable) {
                // Auth not available
            }

            // Get Symfony version
            $frameworkVersion = \Symfony\Component\HttpKernel\Kernel::VERSION;

            return new ApplicationContext(
                environment: $environment,
                route: $route,
                controller: $controller,
                action: $action,
                userId: $userId,
                framework: 'Symfony',
                frameworkVersion: $frameworkVersion,
                extra: [],
            );
        } catch (Throwable) {
            // Fallback to basic context
            return new ApplicationContext(
                environment: $this->kernel->getEnvironment(),
                framework: 'Symfony',
                frameworkVersion: \Symfony\Component\HttpKernel\Kernel::VERSION,
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
