<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Engine\RootCause;

use ClarityPHP\RuntimeInsight\Engine\RootCause\PrimaryCauseInferencer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PrimaryCauseInferencerForeignKeyTest extends TestCase
{
    #[Test]
    public function it_categorizes_foreign_key_violation(): void
    {
        $inf = new PrimaryCauseInferencer();
        $r = $inf->infer('PDOException', 'foreign key constraint fails');

        $this->assertSame(PrimaryCauseInferencer::CATEGORY_SQL, $r['category']);
    }
}
