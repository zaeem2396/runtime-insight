<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Event;

use ClarityPHP\RuntimeInsight\DTO\Explanation;
use ClarityPHP\RuntimeInsight\DTO\ExceptionInfo;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use ClarityPHP\RuntimeInsight\DTO\SourceContext;
use ClarityPHP\RuntimeInsight\DTO\StackTraceInfo;
use ClarityPHP\RuntimeInsight\Event\AfterAnalysisEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AfterAnalysisEventTest extends TestCase
{
    #[Test]
    public function it_holds_explanation_and_context(): void
    {
        $explanation = new Explanation('Error', 'Cause', [], 0.9);
        $context = new RuntimeContext(
            exception: new ExceptionInfo('E', 'msg', 0, '/f', 1),
            stackTrace: new StackTraceInfo([]),
            sourceContext: SourceContext::empty(),
        );
        $event = new AfterAnalysisEvent($explanation, $context);

        $this->assertSame($explanation, $event->explanation);
        $this->assertSame($context, $event->context);
    }
}
