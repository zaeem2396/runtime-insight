<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Engine\Strategies;

use ClarityPHP\RuntimeInsight\DTO\ExceptionInfo;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use ClarityPHP\RuntimeInsight\DTO\SourceContext;
use ClarityPHP\RuntimeInsight\DTO\StackTraceInfo;
use ClarityPHP\RuntimeInsight\Engine\Strategies\TypeErrorStrategy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TypeErrorStrategyTest extends TestCase
{
    private TypeErrorStrategy $strategy;

    protected function setUp(): void
    {
        $this->strategy = new TypeErrorStrategy();
    }

    #[Test]
    #[DataProvider('typeErrorsProvider')]
    public function it_supports_type_errors(string $message): void
    {
        $context = $this->createContext($message, 'TypeError');

        $this->assertTrue($this->strategy->supports($context));
    }

    /**
     * @return array<string, array{message: string}>
     */
    public static function typeErrorsProvider(): array
    {
        return [
            'argument type mismatch' => [
                'message' => 'Argument #1 must be of type string, int given',
            ],
            'argument with variable name' => [
                'message' => 'Argument #2 ($name) must be of type string, null given',
            ],
            'return type mismatch' => [
                'message' => 'Return value must be of type array, null returned',
            ],
            'property type mismatch' => [
                'message' => 'Cannot assign string to property User::$age of type int',
            ],
        ];
    }

    #[Test]
    public function it_does_not_support_null_pointer_errors(): void
    {
        $context = $this->createContext(
            'Call to a member function getId() on null',
            'TypeError',
        );

        $this->assertFalse($this->strategy->supports($context));
    }

    #[Test]
    public function it_explains_argument_type_error(): void
    {
        $context = $this->createContext(
            'Argument #1 must be of type string, int given',
            'TypeError',
        );

        $explanation = $this->strategy->explain($context);

        $this->assertStringContainsString('string', $explanation->getCause());
        $this->assertStringContainsString('int', $explanation->getCause());
        $this->assertSame(0.90, $explanation->getConfidence());
        $this->assertSame('TypeError', $explanation->getErrorType());
    }

    #[Test]
    public function it_explains_return_type_error(): void
    {
        $context = $this->createContext(
            'Return value must be of type array, null returned',
            'TypeError',
        );

        $explanation = $this->strategy->explain($context);

        $this->assertStringContainsString('array', $explanation->getCause());
        $this->assertStringContainsString('null', $explanation->getCause());
        $this->assertStringContainsString('return', strtolower($explanation->getCause()));
    }

    #[Test]
    public function it_suggests_nullable_type_for_null_actual(): void
    {
        $context = $this->createContext(
            'Argument #1 must be of type string, null given',
            'TypeError',
        );

        $explanation = $this->strategy->explain($context);
        $suggestions = implode(' ', $explanation->getSuggestions());

        $this->assertStringContainsString('nullable', strtolower($suggestions));
    }

    #[Test]
    public function it_has_correct_priority(): void
    {
        $this->assertSame(90, $this->strategy->priority());
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
