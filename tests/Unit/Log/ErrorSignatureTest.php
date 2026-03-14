<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Log;

use ClarityPHP\RuntimeInsight\DTO\LogEntry;
use ClarityPHP\RuntimeInsight\Log\ErrorSignature;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ErrorSignatureTest extends TestCase
{
    #[Test]
    public function from_entry_returns_class_pipe_message_pipe_file_line(): void
    {
        $entry = new LogEntry('Null given', '/app/Foo.php', 10, 'TypeError', null);

        $sig = ErrorSignature::fromEntry($entry);

        $this->assertSame('TypeError|Null given|/app/Foo.php:10', $sig);
    }
}
