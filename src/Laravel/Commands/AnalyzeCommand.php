<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Laravel\Commands;

use ClarityPHP\RuntimeInsight\Contracts\LogParserInterface;
use ClarityPHP\RuntimeInsight\Log\LogAnalyzerService;
use Illuminate\Console\Command;

use function is_readable;
use function is_string;
use function sprintf;
use function strlen;
use function substr;

/**
 * Artisan command to analyze a log file: summarize errors and top failures.
 */
final class AnalyzeCommand extends Command
{
    protected $signature = 'runtime:analyze
                            {log? : Path to log file (e.g. storage/logs/laravel.log)}
                            {--top=10 : Number of top failures to show}';

    protected $description = 'Analyze log file: summarize error types and counts, highlight top failures';

    public function __construct(
        private readonly LogParserInterface $parser,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $logArg = $this->argument('log');
        $logPath = is_string($logArg) ? $logArg : (string) storage_path('logs/laravel.log');
        if ($logPath === '') {
            $logPath = (string) storage_path('logs/laravel.log');
        }

        if (! is_readable($logPath)) {
            $this->error("Log file not found or not readable: {$logPath}");

            return self::FAILURE;
        }

        $top = $this->option('top');
        $topLimit = is_numeric($top) ? (int) $top : 10;
        $topLimit = $topLimit < 1 ? 10 : $topLimit;

        $service = new LogAnalyzerService($this->parser, $topLimit);
        $result = $service->analyze($logPath);

        if ($result->isEmpty()) {
            $this->warn('No errors found in log file.');

            return self::SUCCESS;
        }

        $this->info('Runtime Insight — Log Analysis: ' . $result->logPath);
        $this->line(str_repeat('=', 60));
        $this->line('Total: ' . $result->totalErrors . ' error(s)');
        $this->newLine();

        if ($result->topFailures !== []) {
            $this->info('Top failures:');
            foreach ($result->topFailures as $i => $row) {
                $sample = $row['sample'];
                $msg = $sample->message;
                if (strlen($msg) > 55) {
                    $msg = substr($msg, 0, 52) . '...';
                }
                $this->line(sprintf(
                    '  %d. %s (%s) — %d',
                    $i + 1,
                    $sample->exceptionClass . ' ' . $msg,
                    $sample->file . ':' . $sample->line,
                    $row['count'],
                ));
            }
            $this->newLine();
        }

        $this->line('Run: php artisan runtime:explain --log=' . $result->logPath . ' --line=<n> for details.');

        return self::SUCCESS;
    }
}
