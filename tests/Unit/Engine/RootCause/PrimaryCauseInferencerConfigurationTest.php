<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Engine\RootCause;

use ClarityPHP\RuntimeInsight\Engine\RootCause\PrimaryCauseInferencer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PrimaryCauseInferencerConfigurationTest extends TestCase
{
    #[Test]
    public function it_categorizes_missing_environment_variable(): void
    {
        $inf = new PrimaryCauseInferencer();
        $r = $inf->infer('RuntimeException', 'Required environment variable APP_KEY is not set.');

        $this->assertSame(PrimaryCauseInferencer::CATEGORY_CONFIGURATION, $r['category']);
    }
}
