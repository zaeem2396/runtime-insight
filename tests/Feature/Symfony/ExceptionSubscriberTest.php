<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Feature\Symfony;

use ClarityPHP\RuntimeInsight\Contracts\AnalyzerInterface;
use ClarityPHP\RuntimeInsight\DTO\Explanation;
use ClarityPHP\RuntimeInsight\Symfony\EventSubscriber\ExceptionSubscriber;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;

final class ExceptionSubscriberTest extends TestCase
{
    private AnalyzerInterface $analyzer;
    private LoggerInterface $logger;
    private ExceptionSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->analyzer = $this->createMock(AnalyzerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->subscriber = new ExceptionSubscriber(
            $this->analyzer,
            $this->logger,
        );
    }

    public function test_it_subscribes_to_kernel_exception_event(): void
    {
        $events = ExceptionSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey('kernel.exception', $events);
        $this->assertIsArray($events['kernel.exception']);
    }

    public function test_it_logs_explanation_when_available(): void
    {
        $exception = new Exception('Test error');
        $request = Request::create('/test');
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception,
        );

        $explanation = new Explanation(
            message: 'Test error',
            cause: 'Test cause',
            suggestions: ['Fix 1', 'Fix 2'],
            confidence: 0.85,
        );

        $this->analyzer
            ->expects($this->once())
            ->method('analyze')
            ->with($exception)
            ->willReturn($explanation);

        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with(
                'Runtime Insight Explanation',
                $this->callback(function (array $context): bool {
                    return isset($context['exception'])
                        && isset($context['explanation'])
                        && $context['explanation']['message'] === 'Test error';
                }),
            );

        $this->subscriber->onKernelException($event);
    }

    public function test_it_does_not_log_when_explanation_is_empty(): void
    {
        $exception = new Exception('Test error');
        $request = Request::create('/test');
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception,
        );

        $this->analyzer
            ->expects($this->once())
            ->method('analyze')
            ->with($exception)
            ->willReturn(Explanation::empty());

        $this->logger
            ->expects($this->never())
            ->method('debug');

        $this->subscriber->onKernelException($event);
    }

    public function test_it_handles_analyzer_exception_gracefully(): void
    {
        $exception = new Exception('Test error');
        $request = Request::create('/test');
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception,
        );

        $this->analyzer
            ->expects($this->once())
            ->method('analyze')
            ->willThrowException(new Exception('Analyzer error'));

        $this->logger
            ->expects($this->never())
            ->method('debug');

        // Should not throw
        $this->subscriber->onKernelException($event);
    }
}

