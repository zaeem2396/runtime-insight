<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Engine\RootCause;

use ClarityPHP\RuntimeInsight\Engine\RootCause\PrimaryCauseInferencer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PrimaryCauseInferencerDivisionByMessageTest extends TestCase
{
    #[Test]
    public function it_detects_division_by_zero_in_message_for_generic_exception(): void
    {
        $inf = new PrimaryCauseInferencer();
        $r = $inf->infer('RuntimeException', 'Division by zero in /app/math.php on line 5');

        $this->assertSame(PrimaryCauseInferencer::CATEGORY_DIVISION_BY_ZERO, $r['category']);
    }
}
