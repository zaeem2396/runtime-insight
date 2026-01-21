<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Context;

use ClarityPHP\RuntimeInsight\Config;
use ClarityPHP\RuntimeInsight\Context\ContextBuilder;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use Exception;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ContextBuilderTest extends TestCase
{
    private ContextBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new ContextBuilder(
            new Config(sourceLines: 5),
        );
    }

    #[Test]
    public function it_builds_runtime_context_from_exception(): void
    {
        $exception = new RuntimeException('Test error message', 500);

        $context = $this->builder->build($exception);

        $this->assertInstanceOf(RuntimeContext::class, $context);
        $this->assertSame(RuntimeException::class, $context->exception->class);
        $this->assertSame('Test error message', $context->exception->message);
        $this->assertSame(500, $context->exception->code);
    }

    #[Test]
    public function it_captures_exception_file_and_line(): void
    {
        $exception = new Exception('Test');

        $context = $this->builder->build($exception);

        $this->assertStringContainsString('ContextBuilderTest.php', $context->exception->file);
        $this->assertIsInt($context->exception->line);
        $this->assertGreaterThan(0, $context->exception->line);
    }

    #[Test]
    public function it_builds_stack_trace_info(): void
    {
        $exception = new Exception('Test');

        $context = $this->builder->build($exception);

        $this->assertNotEmpty($context->stackTrace->frames);
        $this->assertNotEmpty($context->stackTrace->rawTrace);
    }

    #[Test]
    public function it_builds_source_context_with_code_snippet(): void
    {
        $exception = new Exception('Test');

        $context = $this->builder->build($exception);

        $this->assertNotEmpty($context->sourceContext->file);
        $this->assertGreaterThan(0, $context->sourceContext->errorLine);
        $this->assertNotEmpty($context->sourceContext->lines);
        $this->assertNotEmpty($context->sourceContext->codeSnippet);
    }

    #[Test]
    public function it_marks_vendor_frames_correctly(): void
    {
        // Create an exception that will have vendor frames in the trace
        $exception = new Exception('Test');

        $context = $this->builder->build($exception);

        // At least the test framework frames should be in the trace
        $this->assertNotEmpty($context->stackTrace->frames);

        // Check that frames have the isVendor property set
        foreach ($context->stackTrace->frames as $frame) {
            $this->assertIsBool($frame->isVendor);
        }
    }

    #[Test]
    public function it_captures_previous_exception(): void
    {
        $previous = new Exception('Previous error');
        $exception = new RuntimeException('Main error', 0, $previous);

        $context = $this->builder->build($exception);

        $this->assertSame(Exception::class, $context->exception->previousClass);
        $this->assertSame('Previous error', $context->exception->previousMessage);
    }

    #[Test]
    public function it_handles_source_context_gracefully(): void
    {
        // Test that source context is built properly for valid files
        $exception = new Exception('Test');

        $context = $this->builder->build($exception);

        // Since this test file exists, source context should not be empty
        $this->assertFalse($context->sourceContext->isEmpty());
        $this->assertStringContainsString('ContextBuilderTest.php', $context->sourceContext->file);
    }

    #[Test]
    public function it_generates_context_summary(): void
    {
        $exception = new RuntimeException('Something went wrong');

        $context = $this->builder->build($exception);
        $summary = $context->toSummary();

        $this->assertStringContainsString('RuntimeException', $summary);
        $this->assertStringContainsString('Something went wrong', $summary);
        $this->assertStringContainsString('File:', $summary);
    }

    #[Test]
    public function it_converts_context_to_array(): void
    {
        $exception = new Exception('Test');

        $context = $this->builder->build($exception);
        $array = $context->toArray();

        $this->assertArrayHasKey('exception', $array);
        $this->assertArrayHasKey('stack_trace', $array);
        $this->assertArrayHasKey('source_context', $array);
        $this->assertArrayHasKey('request_context', $array);
        $this->assertArrayHasKey('application_context', $array);
    }

    #[Test]
    public function it_respects_source_lines_config(): void
    {
        $builder = new ContextBuilder(
            new Config(sourceLines: 2),
        );

        $exception = new Exception('Test');
        $context = $builder->build($exception);

        // Should have at most 5 lines (2 before + error line + 2 after)
        $this->assertLessThanOrEqual(5, count($context->sourceContext->lines));
    }
}

