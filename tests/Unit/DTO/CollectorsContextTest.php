<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\DTO;

use ClarityPHP\RuntimeInsight\DTO\CollectorsContext;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CollectorsContextTest extends TestCase
{
    #[Test]
    public function is_empty_returns_true_when_no_signals(): void
    {
        $ctx = new CollectorsContext();
        $this->assertTrue($ctx->isEmpty());
    }

    #[Test]
    public function is_empty_returns_false_when_signals_present(): void
    {
        $ctx = new CollectorsContext(signals: ['exception' => ['class' => 'Exception']]);
        $this->assertFalse($ctx->isEmpty());
    }

    #[Test]
    public function to_array_returns_signals_under_signals_key(): void
    {
        $signals = ['query' => ['count' => 5]];
        $ctx = new CollectorsContext(signals: $signals);
        $this->assertSame(['signals' => $signals], $ctx->toArray());
    }
}
