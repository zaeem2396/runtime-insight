<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Contracts;

use ClarityPHP\RuntimeInsight\DTO\Explanation;

/**
 * Renders explanations to various output formats.
 */
interface RendererInterface
{
    /**
     * Render the explanation.
     */
    public function render(Explanation $explanation): string;
}
