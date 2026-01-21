<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Engine\Strategies;

use ClarityPHP\RuntimeInsight\DTO\ExceptionInfo;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use ClarityPHP\RuntimeInsight\DTO\SourceContext;
use ClarityPHP\RuntimeInsight\DTO\StackTraceInfo;
use ClarityPHP\RuntimeInsight\Engine\Strategies\NullPointerStrategy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class NullPointerStrategyTest extends TestCase
{
    private NullPointerStrategy $strategy;

    protected function setUp(): void
    {
        $this->strategy = new NullPointerStrategy();
    }

    #[Test]
    #[DataProvider('nullPointerErrorsProvider')]
    public function it_supports_null_pointer_errors(string $message, string $class): void
    {
        $context = $this->createContext($message, $class);

        $this->assertTrue($this->strategy->supports($context));
    }

    /**
     * @return array<string, array{message: string, class: string}>
     */
    public static function nullPointerErrorsProvider(): array
    {
        return [
            'method call on null' => [
                'message' => 'Call to a member function getId() on null',
                'class' => 'TypeError',
            ],
            'property read on null' => [
                'message' => 'Attempt to read property "name" on null',
                'class' => 'Error',
            ],
            'property access on null' => [
                'message' => 'Cannot access property id on null',
                'class' => 'Error',
            ],
            'property assign on null' => [
                'message' => 'Attempt to assign property "value" on null',
                'class' => 'Error',
            ],
        ];
    }

    #[Test]
    public function it_does_not_support_non_null_errors(): void
    {
        $context = $this->createContext('Division by zero', 'DivisionByZeroError');

        $this->assertFalse($this->strategy->supports($context));
    }

    #[Test]
    public function it_explains_method_call_on_null(): void
    {
        $context = $this->createContext(
            'Call to a member function getId() on null',
            'TypeError',
        );

        $explanation = $this->strategy->explain($context);

        $this->assertStringContainsString('getId()', $explanation->getCause());
        $this->assertStringContainsString('null', $explanation->getCause());
        $this->assertSame(0.85, $explanation->getConfidence());
        $this->assertNotEmpty($explanation->getSuggestions());
    }

    #[Test]
    public function it_suggests_nullsafe_operator(): void
    {
        $context = $this->createContext(
            'Call to a member function getName() on null',
            'TypeError',
        );

        $explanation = $this->strategy->explain($context);
        $suggestions = $explanation->getSuggestions();

        $this->assertNotEmpty($suggestions);
        $this->assertTrue(
            collect($suggestions)->contains(fn ($s) => str_contains($s, '?->')),
            'Should suggest nullsafe operator',
        );
    }

    #[Test]
    public function it_has_correct_priority(): void
    {
        $this->assertSame(100, $this->strategy->priority());
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

/**
 * Simple collect helper for tests.
 *
 * @template T
 *
 * @param array<T> $items
 *
 * @return object
 */
function collect(array $items): object
{
    return new class ($items) {
        /**
         * @param array<mixed> $items
         */
        public function __construct(private array $items) {}

        public function contains(callable $callback): bool
        {
            foreach ($this->items as $item) {
                if ($callback($item)) {
                    return true;
                }
            }

            return false;
        }
    };
}

