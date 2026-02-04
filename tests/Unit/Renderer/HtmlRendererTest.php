<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Renderer;

use ClarityPHP\RuntimeInsight\DTO\Explanation;
use ClarityPHP\RuntimeInsight\Renderer\HtmlRenderer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class HtmlRendererTest extends TestCase
{
    #[Test]
    public function it_renders_explanation_as_html(): void
    {
        $explanation = new Explanation(
            message: 'Undefined index',
            cause: 'Key "id" was missing',
            suggestions: ['Check array key exists'],
            confidence: 0.92,
        );

        $renderer = new HtmlRenderer();
        $output = $renderer->render($explanation);

        $this->assertStringContainsString('<!DOCTYPE html>', $output);
        $this->assertStringContainsString('Runtime Error Explained', $output);
        $this->assertStringContainsString('Undefined index', $output);
        $this->assertStringContainsString('Key &quot;id&quot; was missing', $output);
        $this->assertStringContainsString('0.92', $output);
    }
}
