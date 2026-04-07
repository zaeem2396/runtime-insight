<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Engine\RootCause;

use ClarityPHP\RuntimeInsight\Engine\RootCause\PrimaryCauseInferencer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PrimaryCauseInferencerParseErrorTest extends TestCase
{
    #[Test]
    public function it_categorizes_parse_error_class(): void
    {
        $inf = new PrimaryCauseInferencer();
        $r = $inf->infer('ParseError', 'syntax error, unexpected end of file');

        $this->assertSame(PrimaryCauseInferencer::CATEGORY_PARSE, $r['category']);
    }
}
