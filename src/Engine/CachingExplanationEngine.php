<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Engine;

use ClarityPHP\RuntimeInsight\Config;
use ClarityPHP\RuntimeInsight\Contracts\ExplanationCacheInterface;
use ClarityPHP\RuntimeInsight\Contracts\ExplanationEngineInterface;
use ClarityPHP\RuntimeInsight\DTO\Explanation;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;

use function hash;
use function json_encode;

/**
 * Decorator that caches explanation results by error signature.
 */
final class CachingExplanationEngine implements ExplanationEngineInterface
{
    private const KEY_PREFIX = 'runtime_insight:';

    public function __construct(
        private readonly ExplanationEngineInterface $delegate,
        private readonly ExplanationCacheInterface $cache,
        private readonly Config $config,
    ) {}

    public function explain(RuntimeContext $context): Explanation
    {
        if (! $this->config->isCacheEnabled()) {
            return $this->delegate->explain($context);
        }

        $key = $this->buildKey($context);
        $cached = $this->cache->get($key);

        if ($cached !== null) {
            return $cached;
        }

        $explanation = $this->delegate->explain($context);
        $this->cache->set($key, $explanation, $this->config->getCacheTtl());

        return $explanation;
    }

    private function buildKey(RuntimeContext $context): string
    {
        $signature = [
            'class' => $context->exception->class,
            'message' => $context->exception->message,
            'file' => $context->exception->file,
            'line' => $context->exception->line,
        ];

        $encoded = json_encode($signature);

        return self::KEY_PREFIX . hash('sha256', $encoded !== false ? $encoded : '');
    }
}
