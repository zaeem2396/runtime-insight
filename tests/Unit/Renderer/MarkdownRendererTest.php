<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Renderer;

use ClarityPHP\RuntimeInsight\DTO\Explanation;
use ClarityPHP\RuntimeInsight\Renderer\MarkdownRenderer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MarkdownRendererTest extends TestCase
{
    #[Test]
    public function it_renders_explanation_as_markdown(): void
    {
        $explanation = new Explanation(
            message: 'Null reference',
            cause: 'Variable was null',
            suggestions: ['Add null check'],
            confidence: 0.88,
            location: 'src/Service.php:10',
        );

        $renderer = new MarkdownRenderer();
        $output = $renderer->render($explanation);

        $this->assertStringContainsString('# Runtime Error Explanation', $output);
        $this->assertStringContainsString('## Error', $output);
        $this->assertStringContainsString('Null reference', $output);
        $this->assertStringContainsString('## Why This Happened', $output);
        $this->assertStringContainsString('`src/Service.php:10`', $output);
        $this->assertStringContainsString('**Confidence:** 0.88', $output);
    }

    #[Test]
    public function it_includes_code_block_and_called_from_in_markdown_when_present(): void
    {
        $explanation = new Explanation(
            message: 'Type error',
            cause: 'Cause',
            suggestions: [],
            confidence: 0.9,
            location: '/app/foo.php:10',
            codeSnippet: "  → 10 | \$x->bar();\n",
            callSiteLocation: '/app/Controller.php:5',
        );

        $renderer = new MarkdownRenderer();
        $output = $renderer->render($explanation);

        $this->assertStringContainsString('## Called From (fix here)', $output);
        $this->assertStringContainsString('`/app/Controller.php:5`', $output);
        $this->assertStringContainsString('## Code Block (to update)', $output);
        $this->assertStringContainsString('10 | $x->bar();', $output);
    }
}
