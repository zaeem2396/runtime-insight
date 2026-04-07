<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Engine\RootCause;

use ClarityPHP\RuntimeInsight\DTO\StackTraceInfo;
use ClarityPHP\RuntimeInsight\Engine\RootCause\StackTraceAnalyzer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class StackTraceAnalyzerEmptyTest extends TestCase
{
    #[Test]
    public function it_returns_zero_counts_for_empty_stack(): void
    {
        $analyzer = new StackTraceAnalyzer();
        $s = $analyzer->summarize(new StackTraceInfo(frames: []));

        $this->assertSame(0, $s['vendor_frames']);
        $this->assertSame(0, $s['app_frames']);
        $this->assertNull($s['first_app_location']);
        $this->assertSame('', $s['narrative']);
    }
}
