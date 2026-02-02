<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Renderer;

use ClarityPHP\RuntimeInsight\Contracts\RendererInterface;
use ClarityPHP\RuntimeInsight\DTO\Explanation;

/**
 * Renders explanation with location (file:line) on first line for IDE link detection.
 */
final class IdeRenderer implements RendererInterface
{
    public function render(Explanation $explanation): string
    {
        $console = new ConsoleOutputRenderer();
        $body = $console->render($explanation);

        if ($explanation->getLocation() !== null && $explanation->getLocation() !== '') {
            return $explanation->getLocation() . "\n\n" . $body;
        }

        return $body;
    }
}
