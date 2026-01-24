<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Feature\Laravel;

use ClarityPHP\RuntimeInsight\Contracts\AnalyzerInterface;
use ClarityPHP\RuntimeInsight\DTO\Explanation;
use ClarityPHP\RuntimeInsight\Laravel\ExceptionHandler;
use Exception;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use TypeError;

final class ExceptionHandlerTest extends TestCase
{
    private ExceptionHandler $handler;
    private AnalyzerInterface&MockObject $analyzer;
    private LoggerInterface&MockObject $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->analyzer = $this->createMock(AnalyzerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->handler = new ExceptionHandler(
            $this->analyzer,
            $this->logger,
        );
    }

    public function test_it_handles_exceptions_and_logs_explanations(): void
    {
        $exception = new TypeError('Call to a member function on null');

        $explanation = new Explanation(
            message: 'Call to a member function on null',
            cause: 'The variable is null',
            suggestions: ['Check for null before calling'],
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
                    return isset($context['explanation']) &&
                           $context['explanation']['message'] === 'Call to a member function on null';
                }),
            );

        $this->handler->handle($exception);
    }

    public function test_it_does_not_log_empty_explanations(): void
    {
        $exception = new Exception('Test');

        $this->analyzer
            ->expects($this->once())
            ->method('analyze')
            ->willReturn(Explanation::empty());

        $this->logger
            ->expects($this->never())
            ->method('debug');

        $this->handler->handle($exception);
    }

    public function test_it_handles_analyzer_exceptions_gracefully(): void
    {
        $exception = new Exception('Test');

        $this->analyzer
            ->expects($this->once())
            ->method('analyze')
            ->willThrowException(new Exception('Analyzer error'));

        // Should not throw
        $this->handler->handle($exception);
    }

    public function test_it_formats_explanation_for_console(): void
    {
        $explanation = new Explanation(
            message: 'Call to a member function on null',
            cause: 'The variable is null because...',
            suggestions: [
                'Check for null before calling',
                'Use nullsafe operator',
            ],
            confidence: 0.85,
            errorType: 'NullPointerError',
            location: 'app/Test.php:42',
        );

        $formatted = $this->handler->formatExplanation($explanation);

        $this->assertStringContainsString('Runtime Error Explained', $formatted);
        $this->assertStringContainsString('Call to a member function on null', $formatted);
        $this->assertStringContainsString('The variable is null because...', $formatted);
        $this->assertStringContainsString('app/Test.php:42', $formatted);
        $this->assertStringContainsString('Check for null before calling', $formatted);
        $this->assertStringContainsString('Use nullsafe operator', $formatted);
        $this->assertStringContainsString('Confidence: 0.85', $formatted);
    }

    public function test_format_explanation_handles_minimal_explanation(): void
    {
        $explanation = new Explanation(
            message: 'Error message',
            cause: '',
            suggestions: [],
            confidence: 0.5,
        );

        $formatted = $this->handler->formatExplanation($explanation);

        $this->assertStringContainsString('Error message', $formatted);
        $this->assertStringContainsString('Confidence: 0.5', $formatted);
        $this->assertStringNotContainsString('Why this happened:', $formatted);
        $this->assertStringNotContainsString('Suggested Fix:', $formatted);
    }
}

