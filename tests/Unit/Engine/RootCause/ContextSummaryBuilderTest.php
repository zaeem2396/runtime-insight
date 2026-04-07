<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Engine\RootCause;

use ClarityPHP\RuntimeInsight\DTO\ExceptionInfo;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use ClarityPHP\RuntimeInsight\DTO\SourceContext;
use ClarityPHP\RuntimeInsight\DTO\StackFrame;
use ClarityPHP\RuntimeInsight\DTO\StackTraceInfo;
use ClarityPHP\RuntimeInsight\Engine\RootCause\ContextSummaryBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ContextSummaryBuilderTest extends TestCase
{
    #[Test]
    public function it_appends_call_chain_excerpt(): void
    {
        $builder = new ContextSummaryBuilder();
        $frames = [
            new StackFrame('/app/C.php', 10, 'C', 'run', '->', isVendor: false),
        ];
        $ctx = new RuntimeContext(
            exception: new ExceptionInfo('E', 'm', 0, '/app/C.php', 10),
            stackTrace: new StackTraceInfo(frames: $frames),
            sourceContext: SourceContext::empty(),
        );

        $summary = $builder->build($ctx, 3);

        $this->assertStringStartsWith('/app/C.php:10', $summary);
        $this->assertStringContainsString('Call chain', $summary);
        $this->assertStringContainsString('#0', $summary);
    }
}
