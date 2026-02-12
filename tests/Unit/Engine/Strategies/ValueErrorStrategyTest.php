<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Engine\Strategies;

use ClarityPHP\RuntimeInsight\DTO\ExceptionInfo;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use ClarityPHP\RuntimeInsight\DTO\SourceContext;
use ClarityPHP\RuntimeInsight\DTO\StackTraceInfo;
use ClarityPHP\RuntimeInsight\Engine\Strategies\ValueErrorStrategy;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ValueErrorStrategyTest extends TestCase
{
    private ValueErrorStrategy $strategy;

    protected function setUp(): void
    {
        $this->strategy = new ValueErrorStrategy();
    }

    #[Test]
    public function it_supports_value_error_class(): void
    {
        $context = $this->createContext('first(): Argument #1 ($array) must be a non-empty array', 'ValueError');

        $this->assertTrue($this->strategy->supports($context));
    }

    #[Test]
    public function it_does_not_support_other_errors(): void
    {
        $context = $this->createContext('Invalid value', 'Exception');

        $this->assertFalse($this->strategy->supports($context));
    }

    #[Test]
    public function it_explains_value_error_with_cause_and_suggestions(): void
    {
        $context = $this->createContext(
            'first(): Argument #1 ($array) must be a non-empty array',
            'ValueError',
        );

        $explanation = $this->strategy->explain($context);

        $this->assertStringContainsString('invalid', $explanation->getCause());
        $this->assertSame(0.85, $explanation->getConfidence());
        $this->assertSame('ValueError', $explanation->getErrorType());
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
                file: '/test/helpers.php',
                line: 10,
            ),
            stackTrace: new StackTraceInfo(frames: []),
            sourceContext: SourceContext::empty(),
        );
    }
}
