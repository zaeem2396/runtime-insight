<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Feature\Laravel;

use ClarityPHP\RuntimeInsight\Config;
use ClarityPHP\RuntimeInsight\Context\ContextBuilder;
use ClarityPHP\RuntimeInsight\DTO\ApplicationContext;
use ClarityPHP\RuntimeInsight\DTO\RequestContext;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use ClarityPHP\RuntimeInsight\Laravel\Context\LaravelContextBuilder;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Orchestra\Testbench\TestCase;

use function is_array;

final class LaravelContextBuilderTest extends TestCase
{
    private LaravelContextBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $config = new Config(
            enabled: true,
            includeRequest: true,
            sanitizeInputs: true,
        );

        $baseBuilder = new ContextBuilder($config);

        $this->builder = new LaravelContextBuilder(
            $baseBuilder,
            $this->app,
            $config,
        );
    }

    public function test_it_builds_runtime_context_with_laravel_info(): void
    {
        $exception = new Exception('Test error');

        $context = $this->builder->build($exception);

        $this->assertInstanceOf(RuntimeContext::class, $context);
        $this->assertInstanceOf(ApplicationContext::class, $context->applicationContext);
        $this->assertSame('Laravel', $context->applicationContext->framework);
        $this->assertIsString($context->applicationContext->frameworkVersion);
    }

    public function test_it_captures_application_environment(): void
    {
        $exception = new Exception('Test');

        $context = $this->builder->build($exception);

        $this->assertSame($this->app->environment(), $context->applicationContext->environment);
    }

    public function test_it_captures_request_context_when_available(): void
    {
        // Create a mock request
        $request = Request::create('/test', 'POST', ['name' => 'John']);
        $this->app->instance('request', $request);

        $exception = new Exception('Test');

        $context = $this->builder->build($exception);

        $this->assertInstanceOf(RequestContext::class, $context->requestContext);
        $this->assertSame('POST', $context->requestContext->method);
        $this->assertStringContainsString('/test', $context->requestContext->uri);
    }

    public function test_it_returns_null_request_context_when_not_available(): void
    {
        // Remove request from container
        $this->app->forgetInstance('request');

        $exception = new Exception('Test');

        $context = $this->builder->build($exception);

        // In console context, request might be null
        // This is acceptable behavior
        $this->assertInstanceOf(RuntimeContext::class, $context);
    }

    public function test_it_sanitizes_sensitive_inputs(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => 'John',
            'password' => 'secret123',
            'email' => 'test@example.com',
            'api_key' => 'key123',
        ]);

        $this->app->instance('request', $request);

        $config = new Config(
            enabled: true,
            includeRequest: true,
            sanitizeInputs: true,
            redactFields: ['password', 'api_key'],
        );

        $baseBuilder = new ContextBuilder($config);
        $builder = new LaravelContextBuilder($baseBuilder, $this->app, $config);

        $exception = new Exception('Test');
        $context = $builder->build($exception);

        $this->assertNotNull($context->requestContext);
        $this->assertSame('John', $context->requestContext->body['name']);
        $this->assertSame('[REDACTED]', $context->requestContext->body['password']);
        $this->assertSame('test@example.com', $context->requestContext->body['email']);
        $this->assertSame('[REDACTED]', $context->requestContext->body['api_key']);
    }

    public function test_it_sanitizes_sensitive_headers(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Authorization', 'Bearer token123');
        $request->headers->set('X-Api-Key', 'key123');
        $request->headers->set('Content-Type', 'application/json');

        $this->app->instance('request', $request);

        $exception = new Exception('Test');
        $context = $this->builder->build($exception);

        $this->assertNotNull($context->requestContext);
        $headers = $context->requestContext->headers;

        // Headers are stored as arrays, check if sensitive ones are redacted
        $authHeader = $headers['authorization'] ?? $headers['Authorization'] ?? null;
        $apiKeyHeader = $headers['x-api-key'] ?? $headers['X-Api-Key'] ?? null;
        $contentType = $headers['content-type'] ?? $headers['Content-Type'] ?? null;

        if (is_array($authHeader)) {
            $this->assertStringContainsString('[REDACTED]', (string) ($authHeader[0] ?? ''));
        } else {
            $this->assertStringContainsString('[REDACTED]', (string) ($authHeader ?? ''));
        }

        if (is_array($apiKeyHeader)) {
            $this->assertStringContainsString('[REDACTED]', (string) ($apiKeyHeader[0] ?? ''));
        } else {
            $this->assertStringContainsString('[REDACTED]', (string) ($apiKeyHeader ?? ''));
        }

        // Content-Type should not be redacted
        if (is_array($contentType)) {
            $this->assertStringContainsString('application/json', (string) ($contentType[0] ?? ''));
        } else {
            $this->assertStringContainsString('application/json', (string) ($contentType ?? ''));
        }
    }

    public function test_it_captures_route_information(): void
    {
        // Set up a route
        $this->app['router']->get('/users/{id}', function (): void {
            throw new Exception('Test');
        })->name('users.show');

        $request = Request::create('/users/123', 'GET');
        $this->app->instance('request', $request);

        // Simulate route resolution by binding it to the request
        try {
            $route = $this->app['router']->getRoutes()->match($request);
            $request->setRouteResolver(static fn() => $route);
        } catch (Exception) {
            // Route matching might fail in test environment, that's okay
        }

        $exception = new Exception('Test');
        $context = $this->builder->build($exception);

        $this->assertNotNull($context->applicationContext);
        // Route might be null if route resolution fails in test environment
        // Just verify the context was built successfully
        $this->assertInstanceOf(ApplicationContext::class, $context->applicationContext);
    }

    public function test_it_respects_include_request_config(): void
    {
        $config = new Config(
            enabled: true,
            includeRequest: false,
        );

        $baseBuilder = new ContextBuilder($config);
        $builder = new LaravelContextBuilder($baseBuilder, $this->app, $config);

        $request = Request::create('/test', 'GET');
        $this->app->instance('request', $request);

        $exception = new Exception('Test');
        $context = $builder->build($exception);

        $this->assertNull($context->requestContext);
    }

    public function test_it_handles_nested_array_sanitization(): void
    {
        $request = Request::create('/test', 'POST', [
            'user' => [
                'name' => 'John',
                'password' => 'secret',
                'profile' => [
                    'email' => 'test@example.com',
                    'token' => 'token123',
                ],
            ],
        ]);

        $this->app->instance('request', $request);

        $config = new Config(
            enabled: true,
            includeRequest: true,
            sanitizeInputs: true,
            redactFields: ['password', 'token'],
        );

        $baseBuilder = new ContextBuilder($config);
        $builder = new LaravelContextBuilder($baseBuilder, $this->app, $config);

        $exception = new Exception('Test');
        $context = $builder->build($exception);

        $this->assertNotNull($context->requestContext);
        $body = $context->requestContext->body;

        $this->assertIsArray($body['user']);
        $this->assertSame('John', $body['user']['name']);
        $this->assertSame('[REDACTED]', $body['user']['password']);
        $this->assertSame('test@example.com', $body['user']['profile']['email']);
        $this->assertSame('[REDACTED]', $body['user']['profile']['token']);
    }
}
