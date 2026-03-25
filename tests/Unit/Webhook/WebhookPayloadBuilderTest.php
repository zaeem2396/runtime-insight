<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Webhook;

use ClarityPHP\RuntimeInsight\DTO\ExceptionInfo;
use ClarityPHP\RuntimeInsight\DTO\Explanation;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use ClarityPHP\RuntimeInsight\DTO\SourceContext;
use ClarityPHP\RuntimeInsight\DTO\StackTraceInfo;
use ClarityPHP\RuntimeInsight\Event\AfterAnalysisEvent;
use ClarityPHP\RuntimeInsight\Webhook\WebhookPayloadBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class WebhookPayloadBuilderTest extends TestCase
{
    #[Test]
    public function build_contains_event_type_and_exception_and_explanation(): void
    {
        $context = new RuntimeContext(
            exception: new ExceptionInfo('RuntimeException', 'oops', 0, '/app/x.php', 10),
            stackTrace: new StackTraceInfo([]),
            sourceContext: SourceContext::empty(),
        );
        $explanation = new Explanation('msg', 'cause', ['fix'], 0.8);
        $event = new AfterAnalysisEvent($explanation, $context);
        $payload = (new WebhookPayloadBuilder())->build($event);

        $this->assertSame('runtime_insight.after_analysis', $payload['event']);
        $this->assertSame('RuntimeException', $payload['exception']['class']);
        $this->assertSame('msg', $payload['explanation']['message']);
    }
}
