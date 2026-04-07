<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Engine\RootCause;

use ClarityPHP\RuntimeInsight\Engine\RootCause\PrimaryCauseInferencer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PrimaryCauseInferencerValidationTest extends TestCase
{
    #[Test]
    public function it_categorizes_validation_failed_messages(): void
    {
        $inf = new PrimaryCauseInferencer();
        $r = $inf->infer('RuntimeException', 'The given data was invalid.');

        $this->assertSame(PrimaryCauseInferencer::CATEGORY_VALIDATION, $r['category']);
    }

    #[Test]
    public function it_categorizes_invalid_argument_with_required(): void
    {
        $inf = new PrimaryCauseInferencer();
        $r = $inf->infer('InvalidArgumentException', 'The email field is required.');

        $this->assertSame(PrimaryCauseInferencer::CATEGORY_VALIDATION, $r['category']);
    }
}
