<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit\Engine;

use ClarityPHP\RuntimeInsight\DTO\DatabaseContext;
use ClarityPHP\RuntimeInsight\DTO\ExceptionInfo;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use ClarityPHP\RuntimeInsight\DTO\SourceContext;
use ClarityPHP\RuntimeInsight\DTO\StackTraceInfo;
use ClarityPHP\RuntimeInsight\Engine\LaravelPatternAnalyzer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LaravelPatternAnalyzerTest extends TestCase
{
    private function createContext(
        string $message = 'Error',
        ?DatabaseContext $databaseContext = null,
    ): RuntimeContext {
        return new RuntimeContext(
            exception: new ExceptionInfo(
                class: 'Exception',
                message: $message,
                code: 0,
                file: '/app/Test.php',
                line: 10,
            ),
            stackTrace: new StackTraceInfo(frames: []),
            sourceContext: SourceContext::empty(),
            databaseContext: $databaseContext,
        );
    }

    #[Test]
    public function it_returns_empty_when_few_queries(): void
    {
        $db = new DatabaseContext(recentQueries: ['SELECT 1', 'SELECT 2']);
        $context = $this->createContext('Error', $db);
        $analyzer = new LaravelPatternAnalyzer();

        $result = $analyzer->analyze($context);

        $this->assertTrue($result->isEmpty());
    }

    #[Test]
    public function it_detects_n_plus_one_hint_when_many_queries(): void
    {
        $queries = [];
        for ($i = 0; $i < 15; $i++) {
            $queries[] = 'SELECT * FROM users WHERE id = ' . $i;
        }
        $db = new DatabaseContext(recentQueries: $queries);
        $context = $this->createContext('Error', $db);
        $analyzer = new LaravelPatternAnalyzer();

        $result = $analyzer->analyze($context);

        $this->assertFalse($result->isEmpty());
        $this->assertSame('n_plus_one', $result->patternName);
        $this->assertStringContainsString('15', $result->summary);
        $this->assertNotEmpty($result->suggestions);
    }

    #[Test]
    public function it_detects_validation_hint(): void
    {
        $context = $this->createContext('The given data was invalid.');
        $analyzer = new LaravelPatternAnalyzer();

        $result = $analyzer->analyze($context);

        $this->assertFalse($result->isEmpty());
        $this->assertSame('validation', $result->patternName);
    }

    #[Test]
    public function it_returns_empty_for_generic_message(): void
    {
        $context = $this->createContext('Something broke');
        $analyzer = new LaravelPatternAnalyzer();

        $result = $analyzer->analyze($context);

        $this->assertTrue($result->isEmpty());
    }

    #[Test]
    public function to_array_contains_expected_keys(): void
    {
        $context = $this->createContext('The given data was invalid.');
        $analyzer = new LaravelPatternAnalyzer();
        $result = $analyzer->analyze($context);
        $arr = $result->toArray();

        $this->assertArrayHasKey('pattern_name', $arr);
        $this->assertArrayHasKey('summary', $arr);
        $this->assertArrayHasKey('location', $arr);
        $this->assertArrayHasKey('suggestions', $arr);
    }
}
