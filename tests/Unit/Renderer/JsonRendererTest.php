<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Renderer;

use ClarityPHP\RuntimeInsight\DTO\Explanation;
use ClarityPHP\RuntimeInsight\Renderer\JsonRenderer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class JsonRendererTest extends TestCase
{
    #[Test]
    public function it_renders_explanation_as_json(): void
    {
        $explanation = new Explanation(
            message: 'Error message',
            cause: 'Cause',
            suggestions: ['Fix 1'],
            confidence: 0.85,
        );

        $renderer = new JsonRenderer();
        $output = $renderer->render($explanation);

        $this->assertStringContainsString('Error message', $output);
        $this->assertStringContainsString('Cause', $output);
        $this->assertStringContainsString('0.85', $output);
        $decoded = json_decode($output, true);
        $this->assertIsArray($decoded);
        $this->assertSame('Error message', $decoded['message']);
    }
}
