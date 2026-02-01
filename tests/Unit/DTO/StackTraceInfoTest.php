<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\DTO;

use ClarityPHP\RuntimeInsight\DTO\StackFrame;
use ClarityPHP\RuntimeInsight\DTO\StackTraceInfo;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class StackTraceInfoTest extends TestCase
{
    #[Test]
    public function get_call_chain_summary_returns_empty_for_no_frames(): void
    {
        $info = new StackTraceInfo(frames: []);

        $this->assertSame('', $info->getCallChainSummary());
        $this->assertSame('', $info->getCallChainSummary(5));
    }

    #[Test]
    public function get_call_chain_summary_formats_single_frame(): void
    {
        $frame = new StackFrame(
            file: '/app/Controller.php',
            line: 42,
            class: 'App\\Controller',
            function: 'index',
            type: '->',
            args: [],
            isVendor: false,
        );
        $info = new StackTraceInfo(frames: [$frame]);

        $summary = $info->getCallChainSummary();

        $this->assertStringContainsString('#0', $summary);
        $this->assertStringContainsString('App\\Controller->index', $summary);
        $this->assertStringContainsString('/app/Controller.php:42', $summary);
    }

    #[Test]
    public function get_call_chain_summary_respects_max_frames(): void
    {
        $frames = [
            new StackFrame('/a.php', 1, 'A', 'a', '::', [], false),
            new StackFrame('/b.php', 2, 'B', 'b', '::', [], false),
            new StackFrame('/c.php', 3, 'C', 'c', '::', [], false),
        ];
        $info = new StackTraceInfo(frames: $frames);

        $summary = $info->getCallChainSummary(2);

        $this->assertStringContainsString('#0', $summary);
        $this->assertStringContainsString('#1', $summary);
        $this->assertStringNotContainsString('#2', $summary);
    }

    #[Test]
    public function get_call_chain_summary_includes_static_call_format(): void
    {
        $frame = new StackFrame(
            file: '/app/Service.php',
            line: 10,
            class: 'App\\Service',
            function: 'run',
            type: '::',
            args: [],
            isVendor: false,
        );
        $info = new StackTraceInfo(frames: [$frame]);

        $summary = $info->getCallChainSummary();

        $this->assertStringContainsString('App\\Service::run', $summary);
    }
}
