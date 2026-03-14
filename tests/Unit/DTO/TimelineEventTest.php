<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\DTO;

use ClarityPHP\RuntimeInsight\DTO\TimelineEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TimelineEventTest extends TestCase
{
    #[Test]
    public function to_array_contains_all_fields(): void
    {
        $event = new TimelineEvent(1.5, 'exception', 'TypeError', 'Null given at Test.php:10');
        $arr = $event->toArray();

        $this->assertSame(1.5, $arr['relative_seconds']);
        $this->assertSame('exception', $arr['type']);
        $this->assertSame('TypeError', $arr['label']);
        $this->assertSame('Null given at Test.php:10', $arr['detail']);
    }
}
