<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Laravel\Facades;

use ClarityPHP\RuntimeInsight\DTO\Explanation;
use Illuminate\Support\Facades\Facade;
use Throwable;

/**
 * @method static Explanation analyze(Throwable $throwable)
 * @method static bool isEnabled()
 * @method static bool isAIEnabled()
 *
 * @see \ClarityPHP\RuntimeInsight\RuntimeInsight
 */
class RuntimeInsight extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'runtime-insight';
    }
}

