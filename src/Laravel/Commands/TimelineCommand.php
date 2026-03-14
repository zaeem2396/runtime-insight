<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Laravel\Commands;

use ClarityPHP\RuntimeInsight\Contracts\LogParserInterface;
use ClarityPHP\RuntimeInsight\Log\TimelineService;
use Illuminate\Console\Command;

use function is_readable;
use function is_string;
use function sprintf;

/**
 * Artisan command to show runtime timeline (last failure events from log).
 * Default log: storage/logs/laravel.log. Use --last=N for entry count.
 */
final class TimelineCommand extends Command
{
    protected $signature = 'runtime:timeline
                            {log? : Path to log file (e.g. storage/logs/laravel.log)}
                            {--last=20 : Number of last log entries to include}';

    protected $description = 'Show runtime timeline: events before the last failure (from log)';

    public function __construct(
        private readonly LogParserInterface $parser,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $logArg = $this->argument('log');
        $logPath = is_string($logArg) && $logArg !== '' ? $logArg : (string) storage_path('logs/laravel.log');
        if ($logPath === '') {
            $logPath = (string) storage_path('logs/laravel.log');
        }

        if (! is_readable($logPath)) {
            $this->error("Log file not found or not readable: {$logPath}");

            return self::FAILURE;
        }

        $last = $this->option('last');
        $lastN = is_numeric($last) ? (int) $last : 20;
        $lastN = $lastN < 1 ? 20 : $lastN;

        $service = new TimelineService($this->parser, $lastN);
        $result = $service->buildFromLog($logPath);

        if ($result->isEmpty()) {
            $this->warn('No events found in log file (or file is empty).');

            return self::SUCCESS;
        }

        $this->info('Runtime Timeline (last failure)');
        $this->line(str_repeat('-', 60)); // separator line

        foreach ($result->events as $event) {
            $this->line(sprintf(
                'T+%.2fs   %-16s %s',
                $event->relativeSeconds,
                $event->label,
                $event->detail !== '' ? $event->detail : '',
            ));
        }

        return self::SUCCESS;
    }
}
