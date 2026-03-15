<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Event;

use ClarityPHP\RuntimeInsight\DTO\ExceptionInfo;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use ClarityPHP\RuntimeInsight\DTO\SourceContext;
use ClarityPHP\RuntimeInsight\DTO\StackTraceInfo;
use ClarityPHP\RuntimeInsight\Event\BeforeAnalysisEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BeforeAnalysisEventTest extends TestCase
{
    #[Test]
    public function it_holds_context(): void
    {
        $context = new RuntimeContext(
            exception: new ExceptionInfo('Exception', 'Test', 0, '/app/Test.php', 10),
            stackTrace: new StackTraceInfo([]),
            sourceContext: SourceContext::empty(),
        );
        $event = new BeforeAnalysisEvent($context);

        $this->assertSame($context, $event->context);
    }
}
