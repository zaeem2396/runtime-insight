<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Renderer;

use ClarityPHP\RuntimeInsight\Contracts\RendererInterface;

/**
 * Factory for obtaining renderers by format name.
 */
final class RendererFactory
{
    /**
     * Get a renderer for the given format.
     *
     * Supported formats: text, json, markdown, html
     */
    public static function forFormat(string $format): RendererInterface
    {
        $format = strtolower($format);

        return match ($format) {
            'json' => new JsonRenderer(),
            'markdown', 'md' => new MarkdownRenderer(),
            'html' => new HtmlRenderer(),
            'ide' => new IdeRenderer(),
            default => new ConsoleOutputRenderer(),
        };
    }

    /**
     * Return list of supported format names.
     *
     * @return array<string>
     */
    public static function supportedFormats(): array
    {
        return ['text', 'json', 'markdown', 'html', 'ide'];
    }
}
