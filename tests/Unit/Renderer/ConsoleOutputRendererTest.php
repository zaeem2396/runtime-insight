<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Renderer;

use ClarityPHP\RuntimeInsight\DTO\Explanation;
use ClarityPHP\RuntimeInsight\Renderer\ConsoleOutputRenderer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ConsoleOutputRendererTest extends TestCase
{
    #[Test]
    public function it_renders_explanation_as_formatted_text(): void
    {
        $explanation = new Explanation(
            message: 'Call to member function on null',
            cause: 'Variable $user is null',
            suggestions: ['Check for null', 'Add guard'],
            confidence: 0.9,
            location: 'App/Controller.php:42',
        );

        $renderer = new ConsoleOutputRenderer();
        $output = $renderer->render($explanation);

        $this->assertStringContainsString('Runtime Error Explained', $output);
        $this->assertStringContainsString('Call to member function on null', $output);
        $this->assertStringContainsString('Variable $user is null', $output);
        $this->assertStringContainsString('App/Controller.php:42', $output);
        $this->assertStringContainsString('Check for null', $output);
        $this->assertStringContainsString('0.9', $output);
    }

    #[Test]
    public function it_includes_code_block_and_called_from_when_present(): void
    {
        $explanation = new Explanation(
            message: 'Type error',
            cause: 'Argument was null',
            suggestions: ['Add null check'],
            confidence: 0.9,
            location: '/app/foo.php:148',
            codeSnippet: "  → 148 | requireString(\$value);\n",
            callSiteLocation: '/app/Controller.php:145',
        );

        $renderer = new ConsoleOutputRenderer();
        $output = $renderer->render($explanation);

        $this->assertStringContainsString('Called from (fix here):', $output);
        $this->assertStringContainsString('/app/Controller.php:145', $output);
        $this->assertStringContainsString('Code block (to update):', $output);
        $this->assertStringContainsString('148 | requireString', $output);
    }
}
