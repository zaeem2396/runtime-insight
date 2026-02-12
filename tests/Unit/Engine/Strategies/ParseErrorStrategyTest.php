<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Engine\Strategies;

use ClarityPHP\RuntimeInsight\DTO\ExceptionInfo;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use ClarityPHP\RuntimeInsight\DTO\SourceContext;
use ClarityPHP\RuntimeInsight\DTO\StackTraceInfo;
use ClarityPHP\RuntimeInsight\Engine\Strategies\ParseErrorStrategy;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ParseErrorStrategyTest extends TestCase
{
    private ParseErrorStrategy $strategy;

    protected function setUp(): void
    {
        $this->strategy = new ParseErrorStrategy();
    }

    #[Test]
    public function it_supports_parse_error_class(): void
    {
        $context = $this->createContext('syntax error, unexpected token', 'ParseError');

        $this->assertTrue($this->strategy->supports($context));
    }

    #[Test]
    public function it_does_not_support_other_errors(): void
    {
        $context = $this->createContext('syntax error', 'Exception');

        $this->assertFalse($this->strategy->supports($context));
    }

    #[Test]
    public function it_explains_parse_error_with_cause_and_suggestions(): void
    {
        $context = $this->createContext(
            'syntax error, unexpected "}", expecting ";"',
            'ParseError',
        );

        $explanation = $this->strategy->explain($context);

        $this->assertStringContainsString('syntax error', $explanation->getCause());
        $this->assertSame(0.88, $explanation->getConfidence());
        $this->assertSame('ParseError', $explanation->getErrorType());
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
                file: '/test/file.php',
                line: 42,
            ),
            stackTrace: new StackTraceInfo(frames: []),
            sourceContext: SourceContext::empty(),
        );
    }
}
