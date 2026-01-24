<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Feature\Symfony;

use ClarityPHP\RuntimeInsight\Config;
use ClarityPHP\RuntimeInsight\Context\ContextBuilder;
use ClarityPHP\RuntimeInsight\DTO\ApplicationContext;
use ClarityPHP\RuntimeInsight\DTO\RequestContext;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use ClarityPHP\RuntimeInsight\Symfony\Context\SymfonyContextBuilder;
use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use function is_array;

final class SymfonyContextBuilderTest extends TestCase
{
    private SymfonyContextBuilder $builder;
    private KernelInterface $kernel;
    private RequestStack $requestStack;
    private RouterInterface $router;
    private mixed $tokenStorage;
    private Config $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new Config(
            enabled: true,
            includeRequest: true,
            sanitizeInputs: true,
        );

        $this->kernel = $this->createMock(KernelInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->router = $this->createMock(RouterInterface::class);
        // Security component is optional - use null for tests
        $this->tokenStorage = null;

        $baseBuilder = new ContextBuilder($this->config);

        $this->builder = new SymfonyContextBuilder(
            $baseBuilder,
            $this->kernel,
            $this->requestStack,
            $this->router,
            $this->tokenStorage,
            $this->config,
        );
    }

    public function test_it_builds_runtime_context_with_symfony_info(): void
    {
        $exception = new Exception('Test error');

        $this->kernel
            ->method('getEnvironment')
            ->willReturn('test');

        $this->requestStack
            ->method('getCurrentRequest')
            ->willReturn(null);

        $context = $this->builder->build($exception);

        $this->assertInstanceOf(RuntimeContext::class, $context);
        $this->assertInstanceOf(ApplicationContext::class, $context->applicationContext);
        $this->assertSame('Symfony', $context->applicationContext->framework);
        $this->assertSame('test', $context->applicationContext->environment);
    }

    public function test_it_captures_request_information(): void
    {
        $exception = new Exception('Test error');
        $request = Request::create('/test?foo=bar', 'POST', ['name' => 'John', 'email' => 'john@example.com']);
        $request->headers->set('User-Agent', 'Test Agent');
        $request->headers->set('Authorization', 'Bearer secret-token');

        $this->kernel
            ->method('getEnvironment')
            ->willReturn('test');

        $this->requestStack
            ->method('getCurrentRequest')
            ->willReturn($request);

        $context = $this->builder->build($exception);

        $this->assertInstanceOf(RequestContext::class, $context->requestContext);
        $this->assertSame('POST', $context->requestContext->method);
        $this->assertStringContainsString('/test', $context->requestContext->uri);
        $this->assertSame('Test Agent', $context->requestContext->userAgent);
        $this->assertArrayHasKey('name', $context->requestContext->body);
        $this->assertArrayHasKey('foo', $context->requestContext->query);
    }

    public function test_it_sanitizes_sensitive_inputs(): void
    {
        $exception = new Exception('Test error');
        $request = Request::create('/test', 'POST', [
            'username' => 'john',
            'password' => 'secret123',
            'api_key' => 'key-123',
            'token' => 'token-456',
        ]);

        $this->kernel
            ->method('getEnvironment')
            ->willReturn('test');

        $this->requestStack
            ->method('getCurrentRequest')
            ->willReturn($request);

        $context = $this->builder->build($exception);

        $this->assertInstanceOf(RequestContext::class, $context->requestContext);
        $body = $context->requestContext->body;
        $this->assertIsArray($body);
        $this->assertSame('john', $body['username']);
        $this->assertSame('[REDACTED]', $body['password']);
        $this->assertSame('[REDACTED]', $body['api_key']);
        $this->assertSame('[REDACTED]', $body['token']);
    }

    public function test_it_sanitizes_sensitive_headers(): void
    {
        $exception = new Exception('Test error');
        $request = Request::create('/test');
        $request->headers->set('Authorization', 'Bearer secret-token');
        $request->headers->set('Cookie', 'session=abc123');
        $request->headers->set('X-Api-Key', 'key-123');
        $request->headers->set('Content-Type', 'application/json');

        $this->kernel
            ->method('getEnvironment')
            ->willReturn('test');

        $this->requestStack
            ->method('getCurrentRequest')
            ->willReturn($request);

        $context = $this->builder->build($exception);

        $this->assertInstanceOf(RequestContext::class, $context->requestContext);
        $headers = $context->requestContext->headers;
        $this->assertIsArray($headers);
        // Headers can be arrays in Symfony, check first element if array
        $authValue = is_array($headers['authorization']) ? $headers['authorization'][0] : $headers['authorization'];
        $cookieValue = is_array($headers['cookie']) ? $headers['cookie'][0] : $headers['cookie'];
        $apiKeyValue = is_array($headers['x-api-key']) ? $headers['x-api-key'][0] : $headers['x-api-key'];
        $contentTypeValue = is_array($headers['content-type']) ? $headers['content-type'][0] : $headers['content-type'];
        $this->assertSame('[REDACTED]', $authValue);
        $this->assertSame('[REDACTED]', $cookieValue);
        $this->assertSame('[REDACTED]', $apiKeyValue);
        $this->assertSame('application/json', $contentTypeValue);
    }

    public function test_it_captures_route_information(): void
    {
        $exception = new Exception('Test error');
        $request = Request::create('/test');
        $request->attributes->set('_route', 'test_route');
        $request->attributes->set('_controller', 'App\\Controller\\TestController::index');

        $this->kernel
            ->method('getEnvironment')
            ->willReturn('test');

        $this->requestStack
            ->method('getCurrentRequest')
            ->willReturn($request);

        $context = $this->builder->build($exception);

        $this->assertSame('test_route', $context->applicationContext->route);
        $this->assertSame('App\\Controller\\TestController', $context->applicationContext->controller);
        $this->assertSame('App\\Controller\\TestController::index', $context->applicationContext->action);
    }

    public function test_it_captures_authenticated_user_id(): void
    {
        if ($this->tokenStorage === null) {
            $this->markTestSkipped('Symfony Security component not available');
        }

        $exception = new Exception('Test error');
        $request = Request::create('/test');

        $user = new class {
            public function getId(): string
            {
                return 'user-123';
            }
        };

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $this->kernel
            ->method('getEnvironment')
            ->willReturn('test');

        $this->requestStack
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->tokenStorage
            ->method('getToken')
            ->willReturn($token);

        $context = $this->builder->build($exception);

        $this->assertSame('user-123', $context->applicationContext->userId);
    }

    public function test_it_handles_missing_request_gracefully(): void
    {
        $exception = new Exception('Test error');

        $this->kernel
            ->method('getEnvironment')
            ->willReturn('test');

        $this->requestStack
            ->method('getCurrentRequest')
            ->willReturn(null);

        $context = $this->builder->build($exception);

        $this->assertNull($context->requestContext);
        $this->assertInstanceOf(ApplicationContext::class, $context->applicationContext);
    }

    public function test_it_respects_include_request_config(): void
    {
        $config = new Config(
            enabled: true,
            includeRequest: false,
        );

        $baseBuilder = new ContextBuilder($config);

        $builder = new SymfonyContextBuilder(
            $baseBuilder,
            $this->kernel,
            $this->requestStack,
            $this->router,
            $this->tokenStorage,
            $config,
        );

        $exception = new Exception('Test error');
        $request = Request::create('/test');

        $this->kernel
            ->method('getEnvironment')
            ->willReturn('test');

        $this->requestStack
            ->method('getCurrentRequest')
            ->willReturn($request);

        $context = $builder->build($exception);

        $this->assertNull($context->requestContext);
    }
}

