<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Feature\Laravel;

use ClarityPHP\RuntimeInsight\DTO\Explanation;
use ClarityPHP\RuntimeInsight\Laravel\Facades\RuntimeInsight;
use Orchestra\Testbench\TestCase;
use TypeError;

final class FacadeTest extends TestCase
{
    public function test_facade_can_analyze_exceptions(): void
    {
        $exception = new TypeError('Call to a member function on null');

        $explanation = RuntimeInsight::analyze($exception);

        $this->assertInstanceOf(Explanation::class, $explanation);
        $this->assertFalse($explanation->isEmpty());
    }

    public function test_facade_checks_if_enabled(): void
    {
        $enabled = RuntimeInsight::isEnabled();

        $this->assertIsBool($enabled);
    }

    public function test_facade_checks_if_ai_enabled(): void
    {
        $aiEnabled = RuntimeInsight::isAIEnabled();

        $this->assertIsBool($aiEnabled);
    }

    public function test_facade_explains_null_pointer_errors(): void
    {
        $exception = new TypeError('Call to a member function getName() on null');

        $explanation = RuntimeInsight::analyze($exception);

        $this->assertSame(0.85, $explanation->getConfidence());
        $this->assertSame('NullPointerError', $explanation->getErrorType());
    }

    protected function getPackageProviders($app): array
    {
        return [
            \ClarityPHP\RuntimeInsight\Laravel\RuntimeInsightServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Ensure Runtime Insight is enabled in tests
        $app['config']->set('runtime-insight.enabled', true);
        $app['config']->set('runtime-insight.ai.enabled', false);
        $app['config']->set('runtime-insight.environments', ['local', 'staging', 'testing']);
    }
}
