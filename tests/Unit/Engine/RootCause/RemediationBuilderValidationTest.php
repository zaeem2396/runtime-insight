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

final class RemediationBuilderValidationTest extends TestCase
{
    #[Test]
    public function it_suggests_aligning_request_rules(): void
    {
        $builder = new RemediationBuilder();
        $ctx = new RuntimeContext(
            exception: new ExceptionInfo('InvalidArgumentException', 'required', 0, '/f.php', 1),
            stackTrace: new StackTraceInfo(frames: []),
            sourceContext: SourceContext::empty(),
        );
        $r = $builder->build(PrimaryCauseInferencer::CATEGORY_VALIDATION, $ctx);

        $joined = implode(' ', $r['fixes']);
        $this->assertStringContainsString('validation', strtolower($joined));
    }
}
