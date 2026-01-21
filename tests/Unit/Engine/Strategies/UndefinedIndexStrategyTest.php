<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Engine\Strategies;

use ClarityPHP\RuntimeInsight\DTO\ExceptionInfo;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use ClarityPHP\RuntimeInsight\DTO\SourceContext;
use ClarityPHP\RuntimeInsight\DTO\StackTraceInfo;
use ClarityPHP\RuntimeInsight\Engine\Strategies\UndefinedIndexStrategy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UndefinedIndexStrategyTest extends TestCase
{
    private UndefinedIndexStrategy $strategy;

    protected function setUp(): void
    {
        $this->strategy = new UndefinedIndexStrategy();
    }

    #[Test]
    #[DataProvider('undefinedIndexErrorsProvider')]
    public function it_supports_undefined_index_errors(string $message): void
    {
        $context = $this->createContext($message, 'ErrorException');

        $this->assertTrue($this->strategy->supports($context));
    }

    /**
     * @return array<string, array{message: string}>
     */
    public static function undefinedIndexErrorsProvider(): array
    {
        return [
            'undefined array key' => [
                'message' => 'Undefined array key "user_id"',
            ],
            'undefined index' => [
                'message' => 'Undefined index: email',
            ],
            'undefined offset' => [
                'message' => 'Undefined offset: 5',
            ],
        ];
    }

    #[Test]
    public function it_does_not_support_other_errors(): void
    {
        $context = $this->createContext('Division by zero', 'DivisionByZeroError');

        $this->assertFalse($this->strategy->supports($context));
    }

    #[Test]
    public function it_explains_undefined_array_key(): void
    {
        $context = $this->createContext(
            'Undefined array key "user_id"',
            'ErrorException',
        );

        $explanation = $this->strategy->explain($context);

        $this->assertStringContainsString('user_id', $explanation->getCause());
        $this->assertSame(0.88, $explanation->getConfidence());
        $this->assertSame('UndefinedIndex', $explanation->getErrorType());
    }

    #[Test]
    public function it_suggests_isset_check(): void
    {
        $context = $this->createContext(
            'Undefined array key "name"',
            'ErrorException',
        );

        $explanation = $this->strategy->explain($context);
        $suggestions = implode(' ', $explanation->getSuggestions());

        $this->assertStringContainsString('isset', $suggestions);
        $this->assertStringContainsString('name', $suggestions);
    }

    #[Test]
    public function it_suggests_null_coalescing_operator(): void
    {
        $context = $this->createContext(
            'Undefined array key "id"',
            'ErrorException',
        );

        $explanation = $this->strategy->explain($context);
        $suggestions = implode(' ', $explanation->getSuggestions());

        $this->assertStringContainsString('??', $suggestions);
    }

    #[Test]
    public function it_has_correct_priority(): void
    {
        $this->assertSame(95, $this->strategy->priority());
    }

    private function createContext(string $message, string $class): RuntimeContext
    {
        return new RuntimeContext(
            exception: new ExceptionInfo(
                class: $class,
                message: $message,
                code: 0,
                file: '/test/file.php',
                line: 10,
            ),
            stackTrace: new StackTraceInfo(frames: []),
            sourceContext: SourceContext::empty(),
        );
    }
}

