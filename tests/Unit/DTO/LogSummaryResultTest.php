<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\DTO;

use ClarityPHP\RuntimeInsight\DTO\LogSummaryResult;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LogSummaryResultTest extends TestCase
{
    #[Test]
    public function is_empty_returns_true_when_total_zero(): void
    {
        $result = new LogSummaryResult('/path.log', 0);

        $this->assertTrue($result->isEmpty());
    }

    #[Test]
    public function to_array_includes_log_path_and_total(): void
    {
        $result = new LogSummaryResult('/var/log/app.log', 5);

        $arr = $result->toArray();
        $this->assertSame('/var/log/app.log', $arr['log_path']);
        $this->assertSame(5, $arr['total_errors']);
    }
}
