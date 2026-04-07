<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Engine\RootCause;

use ClarityPHP\RuntimeInsight\Engine\RootCause\PrimaryCauseInferencer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PrimaryCauseInferencerPdoTest extends TestCase
{
    #[Test]
    public function it_categorizes_sqlstate_messages(): void
    {
        $inf = new PrimaryCauseInferencer();
        $r = $inf->infer('PDOException', 'SQLSTATE[23000]: Integrity constraint violation');

        $this->assertSame(PrimaryCauseInferencer::CATEGORY_SQL, $r['category']);
        $this->assertStringContainsString('database', strtolower($r['primary']));
    }
}
