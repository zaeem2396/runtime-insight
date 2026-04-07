<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Engine\RootCause;

use ClarityPHP\RuntimeInsight\DTO\ExceptionInfo;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use ClarityPHP\RuntimeInsight\DTO\SourceContext;
use ClarityPHP\RuntimeInsight\DTO\StackTraceInfo;
use ClarityPHP\RuntimeInsight\Engine\RootCause\PrimaryCauseInferencer;
use ClarityPHP\RuntimeInsight\Engine\RootCause\RemediationBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RemediationBuilderDivZeroTest extends TestCase
{
    #[Test]
    public function it_suggests_guarding_divisor(): void
    {
        $builder = new RemediationBuilder();
        $ctx = new RuntimeContext(
            exception: new ExceptionInfo('DivisionByZeroError', 'x', 0, '/f.php', 1),
            stackTrace: new StackTraceInfo(frames: []),
            sourceContext: SourceContext::empty(),
        );
        $r = $builder->build(PrimaryCauseInferencer::CATEGORY_DIVISION_BY_ZERO, $ctx);

        $joined = implode(' ', $r['fixes']);
        $this->assertStringContainsString('divisor', strtolower($joined));
    }
}
