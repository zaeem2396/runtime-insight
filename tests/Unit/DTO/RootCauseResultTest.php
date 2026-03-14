<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\DTO;

use ClarityPHP\RuntimeInsight\DTO\RootCauseResult;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RootCauseResultTest extends TestCase
{
    #[Test]
    public function is_empty_returns_true_when_primary_cause_empty(): void
    {
        $result = new RootCauseResult(primaryCause: '');
        $this->assertTrue($result->isEmpty());
    }

    #[Test]
    public function is_empty_returns_false_when_primary_cause_set(): void
    {
        $result = new RootCauseResult(primaryCause: 'Null reference');
        $this->assertFalse($result->isEmpty());
    }

    #[Test]
    public function to_array_returns_all_fields(): void
    {
        $result = new RootCauseResult(
            primaryCause: 'Test',
            contributing: 'Contrib',
            contextSummary: 'Summary',
            fixSuggestions: ['Fix 1'],
            preventionAdvice: ['Prevent 1'],
        );
        $arr = $result->toArray();
        $this->assertSame('Test', $arr['primary_cause']);
        $this->assertSame('Contrib', $arr['contributing']);
        $this->assertSame('Summary', $arr['context_summary']);
        $this->assertSame(['Fix 1'], $arr['fix_suggestions']);
        $this->assertSame(['Prevent 1'], $arr['prevention_advice']);
    }
}
