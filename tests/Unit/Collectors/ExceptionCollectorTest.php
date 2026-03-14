<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Collectors;

use ClarityPHP\RuntimeInsight\Collectors\ExceptionCollector;
use ClarityPHP\RuntimeInsight\DTO\ExceptionInfo;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use ClarityPHP\RuntimeInsight\DTO\SourceContext;
use ClarityPHP\RuntimeInsight\DTO\StackTraceInfo;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ExceptionCollectorTest extends TestCase
{
    #[Test]
    public function it_returns_exception_signal(): void
    {
        $context = new RuntimeContext(
            exception: new ExceptionInfo(
                class: 'TypeError',
                message: 'Null given',
                code: 0,
                file: '/app/Test.php',
                line: 42,
            ),
            stackTrace: new StackTraceInfo(frames: []),
            sourceContext: SourceContext::empty(),
        );
        $collector = new ExceptionCollector();

        $payload = $collector->collect($context);

        $this->assertIsArray($payload);
        $this->assertSame('TypeError', $payload['class']);
        $this->assertSame('Null given', $payload['message']);
        $this->assertSame('/app/Test.php', $payload['file']);
        $this->assertSame(42, $payload['line']);
        $this->assertSame(0, $payload['code']);
    }

    #[Test]
    public function get_name_returns_exception(): void
    {
        $collector = new ExceptionCollector();
        $this->assertSame('exception', $collector->getName());
    }

    #[Test]
    public function is_enabled_returns_true(): void
    {
        $collector = new ExceptionCollector();
        $this->assertTrue($collector->isEnabled());
    }
}
