<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Engine\Strategies;

use ClarityPHP\RuntimeInsight\DTO\ExceptionInfo;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use ClarityPHP\RuntimeInsight\DTO\SourceContext;
use ClarityPHP\RuntimeInsight\DTO\StackTraceInfo;
use ClarityPHP\RuntimeInsight\Engine\Strategies\DivisionByZeroErrorStrategy;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DivisionByZeroErrorStrategyTest extends TestCase
{
    private DivisionByZeroErrorStrategy $strategy;

    protected function setUp(): void
    {
        $this->strategy = new DivisionByZeroErrorStrategy();
    }

    #[Test]
    public function it_supports_division_by_zero_error_class(): void
    {
        $context = $this->createContext('Division by zero', 'DivisionByZeroError');

        $this->assertTrue($this->strategy->supports($context));
    }

    #[Test]
    public function it_supports_division_by_zero_message(): void
    {
        $context = $this->createContext('Division by zero', 'Error');

        $this->assertTrue($this->strategy->supports($context));
    }

    #[Test]
    public function it_does_not_support_unrelated_errors(): void
    {
        $context = $this->createContext('Undefined array key "x"', 'Error');

        $this->assertFalse($this->strategy->supports($context));
    }

    #[Test]
    public function it_explains_division_by_zero_with_cause_and_suggestions(): void
    {
        $context = $this->createContext('Division by zero', 'DivisionByZeroError');

        $explanation = $this->strategy->explain($context);

        $this->assertStringContainsString('division by zero', $explanation->getCause());
        $this->assertSame(0.90, $explanation->getConfidence());
        $this->assertSame('DivisionByZeroError', $explanation->getErrorType());
        $this->assertNotEmpty($explanation->getSuggestions());
    }

    #[Test]
    public function it_has_correct_priority(): void
    {
        $this->assertSame(82, $this->strategy->priority());
    }

    private function createContext(string $message, string $class): RuntimeContext
    {
        return new RuntimeContext(
            exception: new ExceptionInfo(
                class: $class,
                message: $message,
                code: 0,
                file: '/test/calc.php',
                line: 5,
            ),
            stackTrace: new StackTraceInfo(frames: []),
            sourceContext: SourceContext::empty(),
        );
    }
}
