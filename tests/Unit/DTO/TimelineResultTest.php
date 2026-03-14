<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\DTO;

use ClarityPHP\RuntimeInsight\DTO\TimelineEvent;
use ClarityPHP\RuntimeInsight\DTO\TimelineResult;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TimelineResultTest extends TestCase
{
    #[Test]
    public function is_empty_returns_true_when_no_events(): void
    {
        $result = new TimelineResult('/path.log', []);

        $this->assertTrue($result->isEmpty());
    }

    #[Test]
    public function to_array_includes_log_path_and_events(): void
    {
        $events = [new TimelineEvent(0.0, 'exception', 'E', 'detail')];
        $result = new TimelineResult('/var/log/app.log', $events);

        $arr = $result->toArray();
        $this->assertSame('/var/log/app.log', $arr['log_path']);
        $this->assertCount(1, $arr['events']);
    }
}
