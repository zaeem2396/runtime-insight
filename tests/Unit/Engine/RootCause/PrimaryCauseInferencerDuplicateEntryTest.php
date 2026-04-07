<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Engine\RootCause;

use ClarityPHP\RuntimeInsight\Engine\RootCause\PrimaryCauseInferencer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PrimaryCauseInferencerDuplicateEntryTest extends TestCase
{
    #[Test]
    public function it_categorizes_duplicate_entry_as_sql(): void
    {
        $inf = new PrimaryCauseInferencer();
        $r = $inf->infer('PDOException', 'Duplicate entry \'1\' for key \'PRIMARY\'');

        $this->assertSame(PrimaryCauseInferencer::CATEGORY_SQL, $r['category']);
    }
}
