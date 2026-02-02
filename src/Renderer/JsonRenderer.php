<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Renderer;

use ClarityPHP\RuntimeInsight\Contracts\RendererInterface;
use ClarityPHP\RuntimeInsight\DTO\Explanation;

use function json_encode;

/**
 * Renders an explanation as JSON.
 */
final class JsonRenderer implements RendererInterface
{
    public function render(Explanation $explanation): string
    {
        $data = $explanation->toArray();
        $encoded = json_encode($data, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES | \JSON_PRETTY_PRINT);

        return $encoded;
    }
}
