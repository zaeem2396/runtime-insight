<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Laravel\Commands;

use ClarityPHP\RuntimeInsight\Contracts\AnalyzerInterface;
use ClarityPHP\RuntimeInsight\Renderer\RendererFactory;
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
                            {--format=text : Output format (text, json, markdown, html, ide)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Explain the most recent runtime error';

    public function __construct(
        private readonly AnalyzerInterface $analyzer,
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
        $renderer = RendererFactory::forFormat(is_string($format) ? $format : 'text');
        $this->line($renderer->render($explanation));
    }
}
