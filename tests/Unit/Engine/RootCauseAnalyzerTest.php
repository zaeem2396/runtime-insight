<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Engine;

use ClarityPHP\RuntimeInsight\DTO\ExceptionInfo;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use ClarityPHP\RuntimeInsight\DTO\SourceContext;
use ClarityPHP\RuntimeInsight\DTO\StackTraceInfo;
use ClarityPHP\RuntimeInsight\Engine\RootCauseAnalyzer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RootCauseAnalyzerTest extends TestCase
{
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
    }
}
