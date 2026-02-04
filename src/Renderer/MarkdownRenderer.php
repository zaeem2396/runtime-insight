<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Renderer;

use ClarityPHP\RuntimeInsight\Contracts\RendererInterface;
use ClarityPHP\RuntimeInsight\DTO\Explanation;

/**
 * Renders an explanation as Markdown.
 */
final class MarkdownRenderer implements RendererInterface
{
    public function render(Explanation $explanation): string
    {
        $output = "# Runtime Error Explanation\n\n";
        $output .= "## Error\n\n";
        $output .= "```\n{$explanation->getMessage()}\n```\n\n";

        if ($explanation->getCause() !== '') {
            $output .= "## Why This Happened\n\n";
            $output .= "{$explanation->getCause()}\n\n";
        }

        if ($explanation->getLocation() !== null) {
            $output .= "## Location\n\n";
            $output .= "`{$explanation->getLocation()}`\n\n";
        }

        $suggestions = $explanation->getSuggestions();
        if ($suggestions !== []) {
            $output .= "## Suggested Fixes\n\n";
            foreach ($suggestions as $suggestion) {
                $output .= "- {$suggestion}\n";
            }
            $output .= "\n";
        }

        $output .= "**Confidence:** {$explanation->getConfidence()}\n";

        return $output;
    }
}
