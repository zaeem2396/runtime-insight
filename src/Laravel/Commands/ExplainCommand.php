<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Laravel\Commands;

use ClarityPHP\RuntimeInsight\Config;
use ClarityPHP\RuntimeInsight\Contracts\AnalyzerInterface;
use ClarityPHP\RuntimeInsight\Renderer\RendererFactory;
use Illuminate\Console\Command;
use Throwable;

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
                            {--format=text : Output format (text, json, markdown, html, ide)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Explain the most recent runtime error';

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
            $this->error('No OpenAI API key found. Set OPEN_AI_APIKEY in your .env file.');

            return self::FAILURE;
        }

        $logFile = $this->option('log');
        $lineOpt = $this->option('line');

        if ($logFile !== null && is_string($logFile)) {
            $entry = $this->parseExceptionFromLog($logFile, $lineOpt !== null ? (int) $lineOpt : null);
            if ($entry === null) {
                return self::FAILURE;
            }
            $explanation = $this->analyzer->analyzeFromLog($entry['message'], $entry['file'], $entry['line']);
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
     * @return array{message: string, file: string, line: int}|null
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

        if ($match === null || ! isset($match[2])) {
            $this->warn('No matching exception found.');

            return null;
        }

        $entry = $match[2];

        // Extract message: Laravel often logs "message {\"exception\":\"...\"}" — use text before " {" when present
        $message = trim($entry);
        if (preg_match('/^(.+?)\s+\{\s*"/s', $entry, $msgMatch) === 1) {
            $message = trim($msgMatch[1]);
        }

        if ($message === '') {
            $message = 'Exception from log';
        }

        // Extract file and line from Laravel exception format: " at /path/file.php:123" or " at path/file.php:123"
        $file = 'unknown';
        $line = 0;
        if (preg_match('/\s+at\s+([^\s:]+(?:\.php)?):(\d+)/', $entry, $locMatch) === 1) {
            $file = $locMatch[1];
            $line = (int) $locMatch[2];
        }

        return ['message' => $message, 'file' => $file, 'line' => $line];
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
