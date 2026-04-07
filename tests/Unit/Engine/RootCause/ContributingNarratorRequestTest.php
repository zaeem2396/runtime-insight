<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Engine\RootCause;

use ClarityPHP\RuntimeInsight\DTO\ExceptionInfo;
use ClarityPHP\RuntimeInsight\DTO\RequestContext;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use ClarityPHP\RuntimeInsight\DTO\SourceContext;
use ClarityPHP\RuntimeInsight\DTO\StackTraceInfo;
use ClarityPHP\RuntimeInsight\Engine\RootCause\ContributingNarrator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ContributingNarratorRequestTest extends TestCase
{
    #[Test]
    public function it_includes_method_uri_and_route(): void
    {
        $narrator = new ContributingNarrator();
        $ctx = new RuntimeContext(
            exception: new ExceptionInfo('E', 'm', 0, '/f.php', 1),
            stackTrace: new StackTraceInfo(frames: []),
            sourceContext: SourceContext::empty(),
            requestContext: new RequestContext('POST', '/api/orders', route: 'orders.store'),
        );
        $stackSummary = ['narrative' => '', 'vendor_frames' => 0, 'app_frames' => 0, 'first_app_location' => null];

        $out = $narrator->narrate($ctx, $stackSummary);

        $this->assertStringContainsString('POST /api/orders', $out);
        $this->assertStringContainsString('Route: orders.store', $out);
    }
}
