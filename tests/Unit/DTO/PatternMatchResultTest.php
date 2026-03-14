<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\DTO;

use ClarityPHP\RuntimeInsight\DTO\PatternMatchResult;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PatternMatchResultTest extends TestCase
{
    #[Test]
    public function is_empty_returns_true_when_pattern_name_empty(): void
    {
        $result = new PatternMatchResult(patternName: '');
        $this->assertTrue($result->isEmpty());
    }

    #[Test]
    public function is_empty_returns_false_when_pattern_name_set(): void
    {
        $result = new PatternMatchResult(patternName: 'n_plus_one');
        $this->assertFalse($result->isEmpty());
    }

    #[Test]
    public function to_array_returns_all_fields(): void
    {
        $result = new PatternMatchResult(
            patternName: 'validation',
            summary: 'Summary',
            location: '/app/Test.php:10',
            suggestions: ['Add rule'],
        );
        $arr = $result->toArray();
        $this->assertSame('validation', $arr['pattern_name']);
        $this->assertSame('Summary', $arr['summary']);
        $this->assertSame('/app/Test.php:10', $arr['location']);
        $this->assertSame(['Add rule'], $arr['suggestions']);
    }
}
