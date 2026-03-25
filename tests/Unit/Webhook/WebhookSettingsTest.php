<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Webhook;

use ClarityPHP\RuntimeInsight\Webhook\WebhookSettings;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class WebhookSettingsTest extends TestCase
{
    #[Test]
    public function disabled_has_no_delivery(): void
    {
        $s = WebhookSettings::disabled();
        $this->assertFalse($s->shouldDeliver());
        $this->assertSame([], $s->getUrls());
    }

    #[Test]
    public function from_config_parses_urls_and_timeout(): void
    {
        $s = WebhookSettings::fromConfigArray([
            'enabled' => true,
            'urls' => ['https://example.com/hook', ''],
            'timeout' => 5,
            'headers' => ['X-Test' => 'a'],
        ]);
        $this->assertTrue($s->shouldDeliver());
        $this->assertSame(['https://example.com/hook'], $s->getUrls());
        $this->assertSame(5, $s->getTimeoutSeconds());
        $this->assertSame(['X-Test' => 'a'], $s->getHeaders());
    }

    #[Test]
    public function enabled_without_urls_does_not_deliver(): void
    {
        $s = WebhookSettings::fromConfigArray(['enabled' => true, 'urls' => []]);
        $this->assertFalse($s->shouldDeliver());
    }

    #[Test]
    public function invalid_input_yields_disabled(): void
    {
        $this->assertFalse(WebhookSettings::fromConfigArray('bad')->shouldDeliver());
    }
}
