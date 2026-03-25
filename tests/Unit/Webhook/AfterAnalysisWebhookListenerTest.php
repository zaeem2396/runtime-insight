<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Webhook;

use ClarityPHP\RuntimeInsight\Contracts\WebhookSenderInterface;
use ClarityPHP\RuntimeInsight\DTO\ExceptionInfo;
use ClarityPHP\RuntimeInsight\DTO\Explanation;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use ClarityPHP\RuntimeInsight\DTO\SourceContext;
use ClarityPHP\RuntimeInsight\DTO\StackTraceInfo;
use ClarityPHP\RuntimeInsight\Event\AfterAnalysisEvent;
use ClarityPHP\RuntimeInsight\Webhook\AfterAnalysisWebhookListener;
use ClarityPHP\RuntimeInsight\Webhook\WebhookPayloadBuilder;
use ClarityPHP\RuntimeInsight\Webhook\WebhookSettings;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function in_array;

final class AfterAnalysisWebhookListenerTest extends TestCase
{
    #[Test]
    public function invoke_posts_to_each_url_when_delivery_enabled(): void
    {
        $settings = WebhookSettings::fromConfigArray([
            'enabled' => true,
            'urls' => ['https://a.test/h', 'https://b.test/h'],
            'timeout' => 2,
            'headers' => ['X-Auth' => 'secret'],
        ]);
        $sender = $this->createMock(WebhookSenderInterface::class);
        $sender->expects($this->exactly(2))->method('post')
            ->with(
                $this->callback(static fn(string $u): bool => in_array($u, ['https://a.test/h', 'https://b.test/h'], true)),
                $this->isString(),
                ['X-Auth' => 'secret'],
            );
        $listener = new AfterAnalysisWebhookListener($settings, $sender, new WebhookPayloadBuilder());
        $event = $this->makeEvent();
        ($listener)($event);
    }

    #[Test]
    public function invoke_skips_when_not_delivering(): void
    {
        $settings = WebhookSettings::disabled();
        $sender = $this->createMock(WebhookSenderInterface::class);
        $sender->expects($this->never())->method('post');
        $listener = new AfterAnalysisWebhookListener($settings, $sender, new WebhookPayloadBuilder());
        ($listener)($this->makeEvent());
    }

    private function makeEvent(): AfterAnalysisEvent
    {
        $context = new RuntimeContext(
            exception: new ExceptionInfo('E', 'm', 0, '/f', 1),
            stackTrace: new StackTraceInfo([]),
            sourceContext: SourceContext::empty(),
        );

        return new AfterAnalysisEvent(new Explanation('x', 'y', [], 0.5), $context);
    }
}
