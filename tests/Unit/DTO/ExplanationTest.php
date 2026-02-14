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

    #[Test]
    public function it_creates_from_array(): void
    {
        $data = [
            'message' => 'From cache message',
            'cause' => 'From cache cause',
            'suggestions' => ['Suggestion one', 'Suggestion two'],
            'confidence' => 0.88,
            'error_type' => 'TypeError',
            'location' => 'App\Service:10',
            'metadata' => ['source' => 'cache'],
        ];

        $explanation = Explanation::fromArray($data);

        $this->assertSame('From cache message', $explanation->getMessage());
        $this->assertSame('From cache cause', $explanation->getCause());
        $this->assertCount(2, $explanation->getSuggestions());
        $this->assertSame(0.88, $explanation->getConfidence());
        $this->assertSame('TypeError', $explanation->getErrorType());
        $this->assertSame('App\Service:10', $explanation->getLocation());
        $this->assertSame(['source' => 'cache'], $explanation->getMetadata());
    }

    #[Test]
    public function it_creates_empty_from_array_with_missing_keys(): void
    {
        $explanation = Explanation::fromArray([]);

        $this->assertSame('', $explanation->getMessage());
        $this->assertSame('', $explanation->getCause());
        $this->assertEmpty($explanation->getSuggestions());
        $this->assertSame(0.0, $explanation->getConfidence());
        $this->assertNull($explanation->getErrorType());
        $this->assertNull($explanation->getLocation());
        $this->assertSame([], $explanation->getMetadata());
        $this->assertNull($explanation->getCodeSnippet());
        $this->assertNull($explanation->getCallSiteLocation());
    }

    #[Test]
    public function it_supports_code_snippet_and_call_site_location(): void
    {
        $explanation = new Explanation(
            message: 'Type error',
            cause: 'Cause',
            suggestions: [],
            confidence: 0.9,
            location: '/app/foo.php:10',
            codeSnippet: '  → 10 | $x->bar();',
            callSiteLocation: '/app/Controller.php:145',
        );

        $this->assertSame('  → 10 | $x->bar();', $explanation->getCodeSnippet());
        $this->assertSame('/app/Controller.php:145', $explanation->getCallSiteLocation());
    }

    #[Test]
    public function it_creates_copy_with_code_context_via_with_code_context(): void
    {
        $base = new Explanation(
            message: 'Error',
            cause: 'Cause',
            suggestions: [],
            confidence: 0.8,
            location: '/app/foo.php:5',
        );

        $enriched = $base->withCodeContext('  → 5 | requireString($value);', '/app/Controller.php:145');

        $this->assertNull($base->getCodeSnippet());
        $this->assertSame('  → 5 | requireString($value);', $enriched->getCodeSnippet());
        $this->assertSame('/app/Controller.php:145', $enriched->getCallSiteLocation());
        $this->assertSame('Error', $enriched->getMessage());
    }

    #[Test]
    public function it_roundtrips_code_snippet_and_call_site_via_to_array_from_array(): void
    {
        $explanation = new Explanation(
            message: 'Err',
            cause: 'Cause',
            suggestions: [],
            confidence: 0.7,
            codeSnippet: '  10 | $a = null;',
            callSiteLocation: '/app/Test.php:20',
        );

        $restored = Explanation::fromArray($explanation->toArray());

        $this->assertSame('  10 | $a = null;', $restored->getCodeSnippet());
        $this->assertSame('/app/Test.php:20', $restored->getCallSiteLocation());
    }
}
