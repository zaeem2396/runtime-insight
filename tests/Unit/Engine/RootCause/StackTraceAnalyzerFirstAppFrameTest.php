<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Engine\RootCause;

use ClarityPHP\RuntimeInsight\DTO\StackFrame;
use ClarityPHP\RuntimeInsight\DTO\StackTraceInfo;
use ClarityPHP\RuntimeInsight\Engine\RootCause\StackTraceAnalyzer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class StackTraceAnalyzerFirstAppFrameTest extends TestCase
{
    #[Test]
    public function it_records_first_application_frame_location(): void
    {
        $analyzer = new StackTraceAnalyzer();
        $frames = [
            new StackFrame('/vendor/v.php', 1, 'V', 'x', '::', isVendor: true),
            new StackFrame('/app/Job.php', 99, 'App\\Job', 'handle', '->', isVendor: false),
        ];
        $s = $analyzer->summarize(new StackTraceInfo(frames: $frames));

        $this->assertSame('/app/Job.php:99', $s['first_app_location']);
        $this->assertStringContainsString('First application frame', $s['narrative']);
    }
}
