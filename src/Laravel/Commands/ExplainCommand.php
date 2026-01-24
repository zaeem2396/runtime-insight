<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Laravel\Commands;

use ClarityPHP\RuntimeInsight\Contracts\AnalyzerInterface;
use ClarityPHP\RuntimeInsight\Laravel\ExceptionHandler;
use Exception;
use Illuminate\Console\Command;
use Throwable;

use function count;
use function file_exists;
use function file_get_contents;
use function is_readable;
use function is_string;
use function preg_match_all;

/**
 * Artisan command to explain the last runtime error.
 */
final class ExplainCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'runtime:explain
                            {--log= : Path to log file to analyze}
                            {--line= : Line number in log file}
                            {--format=text : Output format (text, json, markdown)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Explain the most recent runtime error';

    public function __construct(
        private readonly AnalyzerInterface $analyzer,
        private readonly ExceptionHandler $exceptionHandler,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $throwable = $this->getException();

        if ($throwable === null) {
            $this->error('No exception found to analyze.');

            return self::FAILURE;
        }

        $explanation = $this->analyzer->analyze($throwable);

        if ($explanation->isEmpty()) {
            $this->warn('Runtime Insight is disabled or no explanation could be generated.');

            return self::SUCCESS;
        }

        $this->outputExplanation($explanation);

        return self::SUCCESS;
    }

    /**
     * Get the exception to analyze.
     */
    private function getException(): ?Throwable
    {
        $logFile = $this->option('log');
        $line = $this->option('line');

        if ($logFile !== null && is_string($logFile)) {
            return $this->parseExceptionFromLog($logFile, $line !== null ? (int) $line : null);
        }

        // Try to get from in-memory buffer (if available)
        // For now, we'll create a sample exception for demonstration
        // In a real implementation, you might store the last exception in cache/session
        return null;
    }

    /**
     * Parse exception from log file.
     */
    private function parseExceptionFromLog(string $logFile, ?int $lineNumber): ?Throwable
    {
        if (! file_exists($logFile) || ! is_readable($logFile)) {
            $this->error("Log file not found or not readable: {$logFile}");

            return null;
        }

        $content = file_get_contents($logFile);
        if ($content === false) {
            $this->error("Could not read log file: {$logFile}");

            return null;
        }

        // Simple regex to find exception patterns
        // In a real implementation, you'd use a proper log parser
        $pattern = '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] .*?\.ERROR: (.+?)(?=\[|\Z)/s';

        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER) === false) {
            $this->warn('No exceptions found in log file.');

            return null;
        }

        if ($lineNumber !== null && isset($matches[$lineNumber - 1])) {
            $match = $matches[$lineNumber - 1];
        } else {
            // Get the last exception
            $match = $matches[count($matches) - 1] ?? null;
        }

        if ($match === null) {
            $this->warn('No matching exception found.');

            return null;
        }

        // For now, create a generic exception
        // In a real implementation, you'd parse the full exception details
        // preg_match guarantees index 2 exists when match succeeds
        /** @phpstan-ignore-next-line */
        return new Exception($match[2] ?? 'Exception from log');
    }

    /**
     * Output the explanation in the requested format.
     */
    private function outputExplanation(\ClarityPHP\RuntimeInsight\DTO\Explanation $explanation): void
    {
        $format = $this->option('format');

        match ($format) {
            'json' => $this->outputJson($explanation),
            'markdown' => $this->outputMarkdown($explanation),
            default => $this->outputText($explanation),
        };
    }

    /**
     * Output explanation as formatted text.
     */
    private function outputText(\ClarityPHP\RuntimeInsight\DTO\Explanation $explanation): void
    {
        $this->line($this->exceptionHandler->formatExplanation($explanation));
    }

    /**
     * Output explanation as JSON.
     */
    private function outputJson(\ClarityPHP\RuntimeInsight\DTO\Explanation $explanation): void
    {
        $json = json_encode($explanation->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $this->line($json !== false ? $json : '{}');
    }

    /**
     * Output explanation as Markdown.
     */
    private function outputMarkdown(\ClarityPHP\RuntimeInsight\DTO\Explanation $explanation): void
    {
        $output = "# Runtime Error Explanation\n\n";
        $output .= "## Error\n\n";
        $output .= "```\n{$explanation->getMessage()}\n```\n\n";

        if ($explanation->getCause() !== '') {
            $output .= "## Why This Happened\n\n";
            $output .= "{$explanation->getCause()}\n\n";
        }

        if ($explanation->getLocation() !== null) {
            $output .= "## Location\n\n";
            $output .= "`{$explanation->getLocation()}`\n\n";
        }

        $suggestions = $explanation->getSuggestions();
        if ($suggestions !== []) {
            $output .= "## Suggested Fixes\n\n";
            foreach ($suggestions as $suggestion) {
                $output .= "- {$suggestion}\n";
            }
            $output .= "\n";
        }

        $output .= "**Confidence:** {$explanation->getConfidence()}\n";

        $this->line($output);
    }
}
