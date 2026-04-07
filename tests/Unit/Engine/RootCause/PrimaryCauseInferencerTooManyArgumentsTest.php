<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Engine\RootCause;

use ClarityPHP\RuntimeInsight\Engine\RootCause\PrimaryCauseInferencer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PrimaryCauseInferencerTooManyArgumentsTest extends TestCase
{
    #[Test]
    public function it_categorizes_too_many_arguments_message(): void
    {
        $inf = new PrimaryCauseInferencer();
        $r = $inf->infer('ArgumentCountError', 'Too many arguments to function foo(), 3 passed');

        $this->assertSame(PrimaryCauseInferencer::CATEGORY_ARGUMENT_COUNT, $r['category']);
    }
}
