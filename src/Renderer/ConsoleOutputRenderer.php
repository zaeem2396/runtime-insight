<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Renderer;

use ClarityPHP\RuntimeInsight\Contracts\RendererInterface;
use ClarityPHP\RuntimeInsight\DTO\Explanation;

/**
 * Renders an explanation as formatted text for console output.
 */
final class ConsoleOutputRenderer implements RendererInterface
{
    public function render(Explanation $explanation): string
    {
        $output = "❗ Runtime Error Explained\n\n";
        $output .= "Error:\n  {$explanation->getMessage()}\n\n";

        if ($explanation->getCause() !== '') {
            $output .= "Why this happened:\n  {$explanation->getCause()}\n\n";
        }

        if ($explanation->getLocation() !== null) {
            $output .= "Where:\n  {$explanation->getLocation()}\n\n";
        }

        $suggestions = $explanation->getSuggestions();
        if ($suggestions !== []) {
            $output .= "Suggested Fix:\n";
            foreach ($suggestions as $suggestion) {
                $output .= "  - {$suggestion}\n";
            }
            $output .= "\n";
        }

        $output .= "Confidence: {$explanation->getConfidence()}\n";

        return $output;
    }
}
