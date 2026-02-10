<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Laravel\Commands;

use ClarityPHP\RuntimeInsight\Config;
use ClarityPHP\RuntimeInsight\Contracts\AnalyzerInterface;
use ClarityPHP\RuntimeInsight\Renderer\RendererFactory;
use Illuminate\Console\Command;
use Throwable;

use function array_slice;
use function count;
use function file_exists;
use function file_get_contents;
use function is_readable;
use function is_string;
use function preg_match;
use function preg_match_all;
use function trim;

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
                            {--all : Analyze all exceptions in the log file (use with --log)}
                            {--limit= : Max number of entries to analyze in batch (default: 10)}
                            {--format=text : Output format (text, json, markdown, html, ide)}
                            {--output= : Write explanation to file instead of console}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Explain the most recent runtime error (use --output to write to a file)';

    public function __construct(
        private readonly Config $config,
        private readonly AnalyzerInterface $analyzer,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->config->isAIConfigured()
            && $this->config->getAIProvider() === 'openai'
            && ($this->config->getAIApiKey() === null || $this->config->getAIApiKey() === '')) {
            $this->error('No OpenAI API key found. Set OPEN_AI_APIKEY or RUNTIME_INSIGHT_AI_KEY in your .env file.');

            return self::FAILURE;
        }

        $logFile = $this->option('log');
        $lineOpt = $this->option('line');
        $all = $this->option('all');

        if ($logFile !== null && is_string($logFile) && $all) {
            return $this->handleBatch($logFile);
        }

        if ($logFile !== null && is_string($logFile)) {
            $entry = $this->parseExceptionFromLog($logFile, $lineOpt !== null ? (int) $lineOpt : null);
            if ($entry === null) {
                return self::FAILURE;
            }
            $explanation = $this->analyzer->analyzeFromLog(
                $entry['message'],
                $entry['file'],
                $entry['line'],
                $entry['exceptionClass'],
            );
        } else {
            $throwable = $this->getException();
            if ($throwable === null) {
                $this->error('No exception found to analyze.');

                return self::FAILURE;
            }
            $explanation = $this->analyzer->analyze($throwable);
        }

        if ($explanation->isEmpty()) {
            $this->warn('Runtime Insight is disabled or no explanation could be generated.');

            return self::SUCCESS;
        }

        $this->outputExplanation($explanation);

        return self::SUCCESS;
    }

    /**
     * Get the exception to analyze (when not using --log).
     *
     * @return Throwable|null Hook for in-memory/cache exception; currently always null.
     */
    private function getException(): ?Throwable
    {
        // Try to get from in-memory buffer (if available)
        // In a real implementation, you might store the last exception in cache/session
        return null;
    }

    /**
     * Parse exception from log file. Returns message, file, and line for the log entry.
     *
     * @return array{message: string, file: string, line: int, exceptionClass: string}|null
     */
    private function parseExceptionFromLog(string $logFile, ?int $lineNumber): ?array
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

        // Match full log entry: from [date] ... .ERROR: until next [date] or end (avoids truncating at "[" in message)
        $pattern = '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] [^\n]*\.ERROR: (.+?)(?=\n\[\d{4}-\d{2}-\d{2}|\Z)/s';

        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER) === false) {
            $this->warn('No exceptions found in log file.');

            return null;
        }

        if ($lineNumber !== null && isset($matches[$lineNumber - 1])) {
            $match = $matches[$lineNumber - 1];
        } else {
            $match = $matches[count($matches) - 1] ?? null;
        }

        if (! isset($match[2])) {
            $this->warn('No matching exception found.');

            return null;
        }

        return $this->parseEntryFromMatch($match[2]);
    }

    /**
     * Parse all exception entries from a log file (for batch analysis).
     *
     * @return array<int, array{message: string, file: string, line: int, exceptionClass: string}>|null
     */
    private function parseAllExceptionsFromLog(string $logFile): ?array
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

        $pattern = '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] [^\n]*\.ERROR: (.+?)(?=\n\[\d{4}-\d{2}-\d{2}|\Z)/s';

        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER) === false) {
            $this->warn('No exceptions found in log file.');

            return null;
        }

        $entries = [];
        foreach ($matches as $match) {
            /** @var array{0: string, 1: string, 2: string} $match */
            $entries[] = $this->parseEntryFromMatch($match[2]);
        }

        return $entries;
    }

    /**
     * Extract message, file, line, and exception class from a raw log entry string.
     * Laravel logs often contain (TypeError(...): or (ErrorException(...): for the real class.
     *
     * @return array{message: string, file: string, line: int, exceptionClass: string}
     */
    private function parseEntryFromMatch(string $entry): array
    {
        $message = trim($entry);
        if (preg_match('/^(.+?)\s+\{\s*"/s', $entry, $msgMatch) === 1) {
            $message = trim($msgMatch[1]);
        }
        if ($message === '') {
            $message = 'Exception from log';
        }

        $file = 'unknown';
        $line = 0;
        if (preg_match('/\s+at\s+([^\s:]+(?:\.php)?):(\d+)/', $entry, $locMatch) === 1) {
            $file = $locMatch[1];
            $line = (int) $locMatch[2];
        }

        $exceptionClass = 'Exception';
        // Match (TypeError(...): or (ErrorException at /path or (ErrorException(...):
        if (preg_match('/\(([A-Za-z_][A-Za-z0-9_]*)\s*(?:[:(]|at\b)/', $entry, $classMatch) === 1) {
            $exceptionClass = $classMatch[1];
        }

        return ['message' => $message, 'file' => $file, 'line' => $line, 'exceptionClass' => $exceptionClass];
    }

    /**
     * Analyze all (or limited) exceptions in a log file and output batch results.
     */
    private function handleBatch(string $logFile): int
    {
        $entries = $this->parseAllExceptionsFromLog($logFile);
        if ($entries === null || $entries === []) {
            return self::FAILURE;
        }

        $limitOpt = $this->option('limit');
        $limit = $limitOpt !== null && is_numeric($limitOpt) ? (int) $limitOpt : 10;
        $limit = $limit < 1 ? 10 : $limit;

        // Take last N entries (most recent)
        $toAnalyze = array_slice($entries, -$limit);

        $explanations = [];
        foreach ($toAnalyze as $entry) {
            $explanations[] = $this->analyzer->analyzeFromLog(
                $entry['message'],
                $entry['file'],
                $entry['line'],
                $entry['exceptionClass'],
            );
        }

        $this->outputBatchExplanations($explanations);

        return self::SUCCESS;
    }

    /**
     * Output multiple explanations (batch mode); optionally write to file.
     *
     * @param array<int, \ClarityPHP\RuntimeInsight\DTO\Explanation> $explanations
     */
    private function outputBatchExplanations(array $explanations): void
    {
        $format = $this->option('format');
        $format = is_string($format) ? $format : 'text';
        $renderer = RendererFactory::forFormat($format);
        $outputPath = $this->option('output');
        $toFile = $outputPath !== null && is_string($outputPath) && $outputPath !== '';

        $count = count($explanations);

        if ($toFile) {
            $parts = [];
            foreach ($explanations as $i => $explanation) {
                if ($count > 1) {
                    $parts[] = '';
                    $parts[] = '--- Exception ' . ($i + 1) . ' / ' . $count . ' ---';
                    $parts[] = '';
                }
                $parts[] = $renderer->render($explanation);
            }
            $content = implode("\n", $parts);
            if (file_put_contents($outputPath, $content) === false) {
                $this->error("Could not write to file: {$outputPath}");

                return;
            }
            $this->info("Explanation written to {$outputPath}");
        } else {
            foreach ($explanations as $i => $explanation) {
                if ($count > 1) {
                    $this->line('');
                    $this->line('--- Exception ' . ($i + 1) . ' / ' . $count . ' ---');
                    $this->line('');
                }
                $this->line($renderer->render($explanation));
            }
        }
    }

    /**
     * Output the explanation in the requested format (console or file).
     */
    private function outputExplanation(\ClarityPHP\RuntimeInsight\DTO\Explanation $explanation): void
    {
        $outputPath = $this->option('output');
        $format = $this->option('format');
        $renderer = RendererFactory::forFormat(is_string($format) ? $format : 'text');
        $content = $renderer->render($explanation);

        if ($outputPath !== null && is_string($outputPath) && $outputPath !== '') {
            if (file_put_contents($outputPath, $content) === false) {
                $this->error("Could not write to file: {$outputPath}");

                return;
            }
            $this->info("Explanation written to {$outputPath}");
        } else {
            $this->line($content);
        }
    }
}
