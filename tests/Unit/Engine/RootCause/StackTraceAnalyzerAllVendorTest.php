<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Engine\RootCause;

use ClarityPHP\RuntimeInsight\DTO\StackFrame;
use ClarityPHP\RuntimeInsight\DTO\StackTraceInfo;
use ClarityPHP\RuntimeInsight\Engine\RootCause\StackTraceAnalyzer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class StackTraceAnalyzerAllVendorTest extends TestCase
{
    #[Test]
    public function it_notes_vendor_only_stack(): void
    {
        $analyzer = new StackTraceAnalyzer();
        $frames = [
            new StackFrame('/vendor/x.php', 1, 'V', 'a', '::', isVendor: true),
        ];
        $s = $analyzer->summarize(new StackTraceInfo(frames: $frames));

        $this->assertSame(1, $s['vendor_frames']);
        $this->assertSame(0, $s['app_frames']);
        $this->assertNull($s['first_app_location']);
        $this->assertStringContainsString('vendor', strtolower($s['narrative']));
    }
}
