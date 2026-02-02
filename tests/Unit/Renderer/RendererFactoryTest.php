<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Renderer;

use ClarityPHP\RuntimeInsight\Renderer\ConsoleOutputRenderer;
use ClarityPHP\RuntimeInsight\Renderer\HtmlRenderer;
use ClarityPHP\RuntimeInsight\Renderer\IdeRenderer;
use ClarityPHP\RuntimeInsight\Renderer\JsonRenderer;
use ClarityPHP\RuntimeInsight\Renderer\MarkdownRenderer;
use ClarityPHP\RuntimeInsight\Renderer\RendererFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RendererFactoryTest extends TestCase
{
    #[Test]
    public function it_returns_console_renderer_for_text(): void
    {
        $renderer = RendererFactory::forFormat('text');
        $this->assertInstanceOf(ConsoleOutputRenderer::class, $renderer);
    }

    #[Test]
    public function it_returns_json_renderer_for_json(): void
    {
        $renderer = RendererFactory::forFormat('json');
        $this->assertInstanceOf(JsonRenderer::class, $renderer);
    }

    #[Test]
    public function it_returns_markdown_renderer_for_markdown_and_md(): void
    {
        $this->assertInstanceOf(MarkdownRenderer::class, RendererFactory::forFormat('markdown'));
        $this->assertInstanceOf(MarkdownRenderer::class, RendererFactory::forFormat('md'));
    }

    #[Test]
    public function it_returns_html_renderer_for_html(): void
    {
        $renderer = RendererFactory::forFormat('html');
        $this->assertInstanceOf(HtmlRenderer::class, $renderer);
    }

    #[Test]
    public function it_returns_ide_renderer_for_ide(): void
    {
        $renderer = RendererFactory::forFormat('ide');
        $this->assertInstanceOf(IdeRenderer::class, $renderer);
    }

    #[Test]
    public function it_returns_console_renderer_for_unknown_format(): void
    {
        $renderer = RendererFactory::forFormat('unknown');
        $this->assertInstanceOf(ConsoleOutputRenderer::class, $renderer);
    }

    #[Test]
    public function it_returns_supported_formats(): void
    {
        $formats = RendererFactory::supportedFormats();
        $this->assertContains('text', $formats);
        $this->assertContains('json', $formats);
        $this->assertContains('markdown', $formats);
        $this->assertContains('html', $formats);
        $this->assertContains('ide', $formats);
    }
}
