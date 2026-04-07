<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Engine\RootCause;

use ClarityPHP\RuntimeInsight\DTO\DatabaseContext;
use ClarityPHP\RuntimeInsight\DTO\ExceptionInfo;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use ClarityPHP\RuntimeInsight\DTO\SourceContext;
use ClarityPHP\RuntimeInsight\DTO\StackTraceInfo;
use ClarityPHP\RuntimeInsight\Engine\RootCause\ContributingNarrator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ContributingNarratorDatabaseTest extends TestCase
{
    #[Test]
    public function it_counts_queries_in_contributing_text(): void
    {
        $narrator = new ContributingNarrator();
        $ctx = new RuntimeContext(
            exception: new ExceptionInfo('E', 'm', 0, '/f.php', 1),
            stackTrace: new StackTraceInfo(frames: []),
            sourceContext: SourceContext::empty(),
            databaseContext: new DatabaseContext(['SELECT 1', 'SELECT 2']),
        );
        $stackSummary = ['narrative' => '', 'vendor_frames' => 0, 'app_frames' => 0, 'first_app_location' => null];

        $out = $narrator->narrate($ctx, $stackSummary);

        $this->assertStringContainsString('2 query', $out);
    }
}
