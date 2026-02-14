<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Renderer;

use ClarityPHP\RuntimeInsight\Contracts\RendererInterface;
use ClarityPHP\RuntimeInsight\DTO\Explanation;

use function htmlspecialchars;

/**
 * Renders an explanation as HTML for debug view.
 */
final class HtmlRenderer implements RendererInterface
{
    public function render(Explanation $explanation): string
    {
        $message = $this->escape($explanation->getMessage());
        $cause = $this->escape($explanation->getCause());
        $location = $explanation->getLocation() !== null ? $this->escape($explanation->getLocation()) : null;
        $confidence = $explanation->getConfidence();

        $suggestionsHtml = '';
        foreach ($explanation->getSuggestions() as $suggestion) {
            $suggestionsHtml .= '<li>' . $this->escape($suggestion) . '</li>';
        }

        $locationSection = $location !== null
            ? "<section><h2>Where</h2><p><code>{$location}</code></p></section>"
            : '';

        $callSite = $explanation->getCallSiteLocation();
        $callSiteSection = $callSite !== null
            ? '<section><h2>Called From (fix here)</h2><p><code>' . $this->escape($callSite) . '</code></p></section>'
            : '';

        $snippet = $explanation->getCodeSnippet();
        $snippetSection = $snippet !== null
            ? '<section><h2>Code Block (to update)</h2><pre><code>' . $this->escape($snippet) . '</code></pre></section>'
            : '';

        $suggestionsSection = $suggestionsHtml !== ''
            ? "<section><h2>Suggested Fixes</h2><ul>{$suggestionsHtml}</ul></section>"
            : '';

        return <<<HTML
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Runtime Error Explained</title>
                <style>
                    body { font-family: system-ui, sans-serif; max-width: 720px; margin: 2rem auto; padding: 0 1rem; }
                    h1 { font-size: 1.25rem; color: #b91c1c; }
                    h2 { font-size: 0.875rem; margin-top: 1.5rem; color: #374151; }
                    section { margin-bottom: 1rem; }
                    code { background: #f3f4f6; padding: 0.125rem 0.375rem; border-radius: 0.25rem; font-size: 0.875rem; }
                    pre { background: #f3f4f6; padding: 0.75rem; border-radius: 0.25rem; font-size: 0.875rem; overflow-x: auto; white-space: pre; }
                    .confidence { font-size: 0.875rem; color: #6b7280; }
                </style>
            </head>
            <body>
                <h1>Runtime Error Explained</h1>
                <section>
                    <h2>Error</h2>
                    <p><code>{$message}</code></p>
                </section>
                <section>
                    <h2>Why This Happened</h2>
                    <p>{$cause}</p>
                </section>
                {$locationSection}
                {$callSiteSection}
                {$snippetSection}
                {$suggestionsSection}
                <p class="confidence"><strong>Confidence:</strong> {$confidence}</p>
            </body>
            </html>
            HTML;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
