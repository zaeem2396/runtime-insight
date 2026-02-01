<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Engine;

use ClarityPHP\RuntimeInsight\DTO\Explanation;
use ClarityPHP\RuntimeInsight\Engine\ArrayExplanationCache;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ArrayExplanationCacheTest extends TestCase
{
    private ArrayExplanationCache $cache;

    protected function setUp(): void
    {
        $this->cache = new ArrayExplanationCache();
    }

    #[Test]
    public function it_returns_null_on_miss(): void
    {
        $this->assertNull($this->cache->get('unknown_key'));
    }

    #[Test]
    public function it_stores_and_retrieves_explanation(): void
    {
        $explanation = new Explanation(
            message: 'Cached message',
            cause: 'Cached cause',
            suggestions: ['Suggestion'],
            confidence: 0.9,
        );

        $this->cache->set('key1', $explanation, 3600);

        $retrieved = $this->cache->get('key1');
        $this->assertInstanceOf(Explanation::class, $retrieved);
        $this->assertSame('Cached message', $retrieved->getMessage());
        $this->assertSame('Cached cause', $retrieved->getCause());
        $this->assertSame(0.9, $retrieved->getConfidence());
    }

    #[Test]
    public function it_returns_null_after_ttl_expires(): void
    {
        $explanation = new Explanation(message: 'Short lived', cause: '', suggestions: [], confidence: 0.5);
        $this->cache->set('ttl_key', $explanation, 1);

        $this->assertInstanceOf(Explanation::class, $this->cache->get('ttl_key'));

        // Wait for TTL to expire
        sleep(2);

        $this->assertNull($this->cache->get('ttl_key'));
    }

    #[Test]
    public function it_keeps_entry_forever_when_ttl_zero(): void
    {
        $explanation = new Explanation(message: 'No TTL', cause: '', suggestions: [], confidence: 0.5);
        $this->cache->set('no_ttl', $explanation, 0);

        $this->assertInstanceOf(Explanation::class, $this->cache->get('no_ttl'));
    }
}
