<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Unit;

use ArgumentCountError;
use ClarityPHP\RuntimeInsight\DTO\Explanation;
use ClarityPHP\RuntimeInsight\RuntimeInsight;
use ClarityPHP\RuntimeInsight\RuntimeInsightFactory;
use Error;
use ErrorException;
use Exception;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TypeError;

final class RuntimeInsightTest extends TestCase
{
    private RuntimeInsight $insight;

    protected function setUp(): void
    {
        $this->insight = RuntimeInsightFactory::create([
            'enabled' => true,
            'ai' => ['enabled' => false],
        ]);
    }

    #[Test]
    public function it_analyzes_exceptions(): void
    {
        $exception = new Exception('Something went wrong');

        $explanation = $this->insight->analyze($exception);

        $this->assertInstanceOf(Explanation::class, $explanation);
        $this->assertFalse($explanation->isEmpty());
    }

    #[Test]
    public function it_returns_empty_explanation_when_disabled(): void
    {
        $insight = RuntimeInsightFactory::create([
            'enabled' => false,
        ]);

        $exception = new Exception('Test');
        $explanation = $insight->analyze($exception);

        $this->assertTrue($explanation->isEmpty());
    }

    #[Test]
    public function it_explains_null_pointer_errors(): void
    {
        $exception = new TypeError('Call to a member function getName() on null');

        $explanation = $this->insight->analyze($exception);

        $this->assertSame(0.85, $explanation->getConfidence());
        $this->assertSame('NullPointerError', $explanation->getErrorType());
        $this->assertStringContainsString('getName()', $explanation->getCause());
    }

    #[Test]
    public function it_explains_type_errors(): void
    {
        $exception = new TypeError('Argument #1 must be of type string, int given');

        $explanation = $this->insight->analyze($exception);

        $this->assertSame(0.90, $explanation->getConfidence());
        $this->assertSame('TypeError', $explanation->getErrorType());
        $this->assertStringContainsString('string', $explanation->getCause());
        $this->assertStringContainsString('int', $explanation->getCause());
    }

    #[Test]
    public function it_explains_undefined_index_errors(): void
    {
        // Create a custom exception that mimics ErrorException for undefined index
        $exception = new class ('Undefined array key "user_id"') extends Exception {};

        // Use the actual ErrorException class name
        $exception = new ErrorException('Undefined array key "user_id"');

        $explanation = $this->insight->analyze($exception);

        $this->assertSame(0.88, $explanation->getConfidence());
        $this->assertSame('UndefinedIndex', $explanation->getErrorType());
    }

    #[Test]
    public function it_explains_class_not_found_errors(): void
    {
        $exception = new Error("Class 'App\\Models\\User' not found");

        $explanation = $this->insight->analyze($exception);

        $this->assertSame(0.88, $explanation->getConfidence());
        $this->assertSame('ClassNotFoundError', $explanation->getErrorType());
    }

    #[Test]
    public function it_explains_argument_count_errors(): void
    {
        $exception = new ArgumentCountError('Too few arguments to function test(), 0 passed and exactly 2 expected');

        $explanation = $this->insight->analyze($exception);

        $this->assertSame(0.92, $explanation->getConfidence());
        $this->assertSame('ArgumentCountError', $explanation->getErrorType());
    }

    #[Test]
    public function it_provides_suggestions_for_all_error_types(): void
    {
        $errors = [
            new TypeError('Call to a member function test() on null'),
            new TypeError('Argument #1 must be of type string, int given'),
            new ErrorException('Undefined array key "test"'),
        ];

        foreach ($errors as $error) {
            $explanation = $this->insight->analyze($error);
            $this->assertNotEmpty($explanation->getSuggestions(), 'Should provide suggestions for: ' . $error->getMessage());
        }
    }

    #[Test]
    public function it_includes_location_in_explanation(): void
    {
        $exception = new Exception('Test error');

        $explanation = $this->insight->analyze($exception);

        $this->assertNotNull($explanation->getLocation());
        $this->assertStringContainsString('.php', $explanation->getLocation());
    }

    #[Test]
    public function it_falls_back_to_generic_explanation_for_unknown_errors(): void
    {
        $exception = new Exception('Some custom error message');

        $explanation = $this->insight->analyze($exception);

        // Should still provide a descriptive fallback explanation (fixes #25)
        $this->assertFalse($explanation->isEmpty());
        $this->assertSame(0.5, $explanation->getConfidence());
        $this->assertStringContainsString('exception', $explanation->getCause());
    }

    #[Test]
    public function it_analyzes_from_log_entry_with_correct_location(): void
    {
        $message = 'Undefined array key "id"';
        $file = '/app/Http/Controllers/OrderController.php';
        $line = 42;

        $explanation = $this->insight->analyzeFromLog($message, $file, $line);

        $this->assertInstanceOf(Explanation::class, $explanation);
        $this->assertFalse($explanation->isEmpty());
        $this->assertSame("{$file}:{$line}", $explanation->getLocation());
    }

    #[Test]
    public function it_analyzes_from_log_entry_with_exception_class_for_strategy_matching(): void
    {
        $message = 'Argument #1 ($name) must be of type string, int given';
        $file = '/app/Http/Controllers/CalcController.php';
        $line = 22;
        $exceptionClass = 'TypeError';

        $explanation = $this->insight->analyzeFromLog($message, $file, $line, $exceptionClass);

        $this->assertInstanceOf(Explanation::class, $explanation);
        $this->assertFalse($explanation->isEmpty());
        $this->assertSame("{$file}:{$line}", $explanation->getLocation());
        // With correct exception class, TypeErrorStrategy matches and returns confidence 0.90
        $this->assertGreaterThanOrEqual(0.9, $explanation->getConfidence());
    }

    #[Test]
    public function it_attaches_root_cause_to_explanation_metadata_when_pipeline_enabled(): void
    {
        $exception = new TypeError('Argument #1 ($x) must be of type string, null given');

        $explanation = $this->insight->analyze($exception);

        $metadata = $explanation->getMetadata();
        $this->assertArrayHasKey('root_cause', $metadata);
        $rootCause = $metadata['root_cause'];
        $this->assertIsArray($rootCause);
        $this->assertArrayHasKey('primary_cause', $rootCause);
        $this->assertStringContainsString('Null value', $rootCause['primary_cause']);
    }

    #[Test]
    public function it_attaches_pattern_to_explanation_metadata_when_pattern_analyzer_matches(): void
    {
        $insight = RuntimeInsightFactory::create([
            'enabled' => true,
            'ai' => ['enabled' => false],
        ]);
        $exception = new \Exception('The given data was invalid.');

        $explanation = $insight->analyze($exception);

        $metadata = $explanation->getMetadata();
        $this->assertArrayHasKey('pattern', $metadata);
        $this->assertSame('validation', $metadata['pattern']['pattern_name']);
    }
}
