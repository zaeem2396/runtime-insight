<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Engine;

use ClarityPHP\RuntimeInsight\DTO\ExceptionInfo;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use ClarityPHP\RuntimeInsight\DTO\SourceContext;
use ClarityPHP\RuntimeInsight\DTO\StackFrame;
use ClarityPHP\RuntimeInsight\DTO\StackTraceInfo;
use ClarityPHP\RuntimeInsight\Engine\RootCause\PrimaryCauseInferencer;
use ClarityPHP\RuntimeInsight\Engine\RootCauseAnalyzer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RootCauseAnalyzerTest extends TestCase
{
    #[Test]
    public function it_infers_type_error_with_null(): void
    {
        $analyzer = new RootCauseAnalyzer();
        $context = $this->createContext('Argument #1 ($value) must be of type string, null given', 'TypeError');

        $result = $analyzer->analyze($context);

        $this->assertFalse($result->isEmpty());
        $this->assertStringContainsString('Null value', $result->primaryCause);
    }

    #[Test]
    public function it_infers_undefined_array_key(): void
    {
        $analyzer = new RootCauseAnalyzer();
        $context = $this->createContext('Undefined array key "foo"');

        $result = $analyzer->analyze($context);

        $this->assertFalse($result->isEmpty());
        $this->assertStringContainsString('array key', $result->primaryCause);
    }

    #[Test]
    public function it_infers_not_found(): void
    {
        $analyzer = new RootCauseAnalyzer();
        $context = $this->createContext('Class "App\\Missing" not found');

        $result = $analyzer->analyze($context);

        $this->assertFalse($result->isEmpty());
        $this->assertStringContainsString('not found', $result->primaryCause);
    }

    #[Test]
    public function it_returns_fallback_for_unknown_exception(): void
    {
        $analyzer = new RootCauseAnalyzer();
        $context = $this->createContext('Something went wrong');

        $result = $analyzer->analyze($context);

        $this->assertFalse($result->isEmpty());
        $this->assertStringContainsString('Runtime failure', $result->primaryCause);
    }

    #[Test]
    public function to_array_contains_expected_keys(): void
    {
        $analyzer = new RootCauseAnalyzer();
        $context = $this->createContext('Test');

        $result = $analyzer->analyze($context);
        $arr = $result->toArray();

        $this->assertArrayHasKey('primary_cause', $arr);
        $this->assertArrayHasKey('contributing', $arr);
        $this->assertArrayHasKey('context_summary', $arr);
        $this->assertArrayHasKey('fix_suggestions', $arr);
        $this->assertArrayHasKey('prevention_advice', $arr);
        $this->assertArrayHasKey('diagnostics', $arr);
        $this->assertArrayHasKey('remediation_category', $arr['diagnostics']);
    }

    #[Test]
    public function it_includes_stack_diagnostics_for_vendor_and_app_frames(): void
    {
        $analyzer = new RootCauseAnalyzer();
        $frames = [
            new StackFrame(
                file: '/app/Http/Controllers/OrderController.php',
                line: 24,
                class: 'App\\Http\\Controllers\\OrderController',
                function: 'index',
                type: '->',
                isVendor: false,
            ),
            new StackFrame(
                file: '/vendor/laravel/framework/Request.php',
                line: 100,
                class: 'Illuminate\\Http\\Request',
                function: 'input',
                type: '->',
                isVendor: true,
            ),
        ];
        $context = new RuntimeContext(
            exception: new ExceptionInfo(
                class: 'TypeError',
                message: 'null given',
                code: 0,
                file: '/app/Http/Controllers/OrderController.php',
                line: 24,
            ),
            stackTrace: new StackTraceInfo(frames: $frames),
            sourceContext: SourceContext::empty(),
        );

        $result = $analyzer->analyze($context);

        $this->assertSame(1, $result->diagnostics['vendor_frames']);
        $this->assertSame(1, $result->diagnostics['app_frames']);
        $this->assertStringContainsString('vendor', $result->contributing);
    }

    #[Test]
    public function it_classifies_division_by_zero(): void
    {
        $analyzer = new RootCauseAnalyzer();
        $context = $this->createContext('Division by zero', 'DivisionByZeroError');

        $result = $analyzer->analyze($context);

        $this->assertSame(PrimaryCauseInferencer::CATEGORY_DIVISION_BY_ZERO, $result->diagnostics['remediation_category']);
        $this->assertStringContainsString('Division by zero', $result->primaryCause);
    }

    #[Test]
    public function it_classifies_argument_count_errors(): void
    {
        $analyzer = new RootCauseAnalyzer();
        $context = $this->createContext('Too few arguments to function foo(), 0 passed', 'ArgumentCountError');

        $result = $analyzer->analyze($context);

        $this->assertSame(PrimaryCauseInferencer::CATEGORY_ARGUMENT_COUNT, $result->diagnostics['remediation_category']);
    }

    private function createContext(string $message, string $class = 'Exception'): RuntimeContext
    {
        return new RuntimeContext(
            exception: new ExceptionInfo(
                class: $class,
                message: $message,
                code: 0,
                file: '/app/Test.php',
                line: 42,
            ),
            stackTrace: new StackTraceInfo(frames: []),
            sourceContext: SourceContext::empty(),
        );
    }
}
