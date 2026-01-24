<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Laravel;

use ClarityPHP\RuntimeInsight\Contracts\AnalyzerInterface;
use ClarityPHP\RuntimeInsight\DTO\Explanation;
use Illuminate\Contracts\Logging\Log;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Exception handler that automatically analyzes exceptions using Runtime Insight.
 *
 * This class can be used to extend Laravel's default exception handler
 * or be registered as a listener to automatically explain exceptions.
 */
final class ExceptionHandler
{
    public function __construct(
        private readonly AnalyzerInterface $analyzer,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Handle an exception and log the explanation.
     *
     * This method should be called from Laravel's exception handler
     * in the report() method.
     */
    public function handle(Throwable $exception): void
    {
        try {
            $explanation = $this->analyzer->analyze($exception);

            if ($explanation->isEmpty()) {
                return;
            }

            $this->logExplanation($explanation, $exception);
        } catch (Throwable) {
            // Silently fail - don't let Runtime Insight break exception handling
        }
    }

    /**
     * Get a formatted explanation string for console output.
     */
    public function formatExplanation(Explanation $explanation): string
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

    /**
     * Log the explanation to Laravel's log.
     */
    private function logExplanation(Explanation $explanation, Throwable $exception): void
    {
        $context = [
            'exception' => $exception::class,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'explanation' => [
                'message' => $explanation->getMessage(),
                'cause' => $explanation->getCause(),
                'suggestions' => $explanation->getSuggestions(),
                'confidence' => $explanation->getConfidence(),
                'error_type' => $explanation->getErrorType(),
                'location' => $explanation->getLocation(),
            ],
        ];

        $this->logger->debug('Runtime Insight Explanation', $context);
    }
}
