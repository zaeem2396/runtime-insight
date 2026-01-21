<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\DTO;

use ClarityPHP\RuntimeInsight\DTO\Explanation;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ExplanationTest extends TestCase
{
    #[Test]
    public function it_creates_explanation_with_all_properties(): void
    {
        $explanation = new Explanation(
            message: 'Call to a member function on null',
            cause: 'The variable $user is null because authentication was bypassed',
            suggestions: [
                'Add authentication middleware',
                'Check for null before accessing',
            ],
            confidence: 0.92,
            errorType: 'TypeError',
            location: 'App\Controllers\OrderController:42',
        );

        $this->assertSame('Call to a member function on null', $explanation->getMessage());
        $this->assertSame('The variable $user is null because authentication was bypassed', $explanation->getCause());
        $this->assertCount(2, $explanation->getSuggestions());
        $this->assertSame(0.92, $explanation->getConfidence());
        $this->assertSame('TypeError', $explanation->getErrorType());
        $this->assertSame('App\Controllers\OrderController:42', $explanation->getLocation());
        $this->assertFalse($explanation->isEmpty());
    }

    #[Test]
    public function it_creates_empty_explanation(): void
    {
        $explanation = Explanation::empty();

        $this->assertSame('', $explanation->getMessage());
        $this->assertSame('', $explanation->getCause());
        $this->assertEmpty($explanation->getSuggestions());
        $this->assertSame(0.0, $explanation->getConfidence());
        $this->assertTrue($explanation->isEmpty());
    }

    #[Test]
    public function it_converts_to_array(): void
    {
        $explanation = new Explanation(
            message: 'Error message',
            cause: 'Error cause',
            suggestions: ['Fix it'],
            confidence: 0.85,
        );

        $array = $explanation->toArray();

        $this->assertArrayHasKey('message', $array);
        $this->assertArrayHasKey('cause', $array);
        $this->assertArrayHasKey('suggestions', $array);
        $this->assertArrayHasKey('confidence', $array);
        $this->assertSame('Error message', $array['message']);
    }
}

