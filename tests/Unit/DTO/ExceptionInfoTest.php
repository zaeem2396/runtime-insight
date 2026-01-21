<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\DTO;

use ClarityPHP\RuntimeInsight\DTO\ExceptionInfo;
use Exception;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ExceptionInfoTest extends TestCase
{
    #[Test]
    public function it_creates_from_throwable(): void
    {
        $exception = new RuntimeException('Something went wrong', 500);

        $info = ExceptionInfo::fromThrowable($exception);

        $this->assertSame(RuntimeException::class, $info->class);
        $this->assertSame('Something went wrong', $info->message);
        $this->assertSame(500, $info->code);
        $this->assertStringContainsString('ExceptionInfoTest.php', $info->file);
        $this->assertIsInt($info->line);
        $this->assertNull($info->previousClass);
        $this->assertNull($info->previousMessage);
    }

    #[Test]
    public function it_captures_previous_exception(): void
    {
        $previous = new Exception('Previous error');
        $exception = new RuntimeException('Main error', 0, $previous);

        $info = ExceptionInfo::fromThrowable($exception);

        $this->assertSame(Exception::class, $info->previousClass);
        $this->assertSame('Previous error', $info->previousMessage);
    }

    #[Test]
    public function it_converts_to_array(): void
    {
        $info = new ExceptionInfo(
            class: RuntimeException::class,
            message: 'Test error',
            code: 100,
            file: '/app/Test.php',
            line: 42,
        );

        $array = $info->toArray();

        $this->assertSame(RuntimeException::class, $array['class']);
        $this->assertSame('Test error', $array['message']);
        $this->assertSame(100, $array['code']);
        $this->assertSame('/app/Test.php', $array['file']);
        $this->assertSame(42, $array['line']);
    }
}

