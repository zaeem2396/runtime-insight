<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Symfony\Command;

use ClarityPHP\RuntimeInsight\Contracts\AnalyzerInterface;
use ClarityPHP\RuntimeInsight\DTO\Explanation;
use ClarityPHP\RuntimeInsight\Renderer\RendererFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

use function array_slice;
use function count;
use function file_exists;
use function file_get_contents;
use function is_int;
use function is_readable;
use function is_string;
use function preg_match;
use function preg_match_all;
use function trim;

/**
 * Console command to explain the last runtime error.
 */
#[AsCommand(
    name: 'runtime:explain',
    description: 'Explain the most recent runtime error',
)]
final class ExplainCommand extends Command
{
    public function __construct(
        private readonly AnalyzerInterface $analyzer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('log', 'l', InputOption::VALUE_REQUIRED, 'Path to log file to analyze')
            ->addOption('line', null, InputOption::VALUE_REQUIRED, 'Line number in log file')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Analyze all exceptions in the log file (use with --log)')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Max number of entries to analyze in batch (default: 10)')
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'Output format (text, json, markdown, html, ide)', 'text');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $logFile = $input->getOption('log');
        $lineOpt = $input->getOption('line');
        $all = $input->getOption('all');

        if ($logFile !== null && is_string($logFile) && $all === true) {
            return $this->handleBatch($logFile, $input, $io);
        }

        if ($logFile !== null && is_string($logFile)) {
            $lineNumber = null;
            if ($lineOpt !== null) {
                if (is_int($lineOpt)) {
                    $lineNumber = $lineOpt;
                } elseif (is_string($lineOpt) && is_numeric($lineOpt)) {
                    $lineNumber = (int) $lineOpt;
                }
            }
            $entry = $this->parseExceptionFromLog($logFile, $lineNumber, $io);
            if ($entry === null) {
                return Command::FAILURE;
            }
            $explanation = $this->analyzer->analyzeFromLog($entry['message'], $entry['file'], $entry['line']);
        } else {
            $throwable = $this->getException($input, $io);
            if ($throwable === null) {
                $io->error('No exception found to analyze.');

                return Command::FAILURE;
            }
            $explanation = $this->analyzer->analyze($throwable);
        }

        if ($explanation->isEmpty()) {
            $io->warning('Runtime Insight is disabled or no explanation could be generated.');

            return Command::SUCCESS;
        }

        $this->outputExplanation($explanation, $input, $io);

        return Command::SUCCESS;
    }

    /**
     * Get the exception to analyze (when not using --log).
     *
     * @return Throwable|null Hook for in-memory exception; currently always null.
     */
    private function getException(InputInterface $input, SymfonyStyle $io): ?Throwable
    {
        return null;
    }

    /**
     * Parse exception from log file. Returns message, file, and line for the log entry.
     *
     * @return array{message: string, file: string, line: int}|null
     */
    private function parseExceptionFromLog(string $logFile, ?int $lineNumber, SymfonyStyle $io): ?array
    {
        if (! file_exists($logFile) || ! is_readable($logFile)) {
            $io->error("Log file not found or not readable: {$logFile}");

            return null;
        }

        $content = file_get_contents($logFile);
        if ($content === false) {
            $io->error("Could not read log file: {$logFile}");

            return null;
        }

        $pattern = '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] [^\n]*\.ERROR: (.+?)(?=\n\[\d{4}-\d{2}-\d{2}|\Z)/s';

        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER) === false) {
            $io->warning('No exceptions found in log file.');

            return null;
        }

        if ($lineNumber !== null && isset($matches[$lineNumber - 1])) {
            $match = $matches[$lineNumber - 1];
        } else {
            $match = $matches[count($matches) - 1] ?? null;
        }

        if (! isset($match[2])) {
            $io->warning('No matching exception found.');

            return null;
        }

        return $this->parseEntryFromMatch($match[2]);
    }

    /**
     * Parse all exception entries from a log file (for batch analysis).
     *
     * @return array<int, array{message: string, file: string, line: int}>|null
     */
    private function parseAllExceptionsFromLog(string $logFile, SymfonyStyle $io): ?array
    {
        if (! file_exists($logFile) || ! is_readable($logFile)) {
            $io->error("Log file not found or not readable: {$logFile}");

            return null;
        }

        $content = file_get_contents($logFile);
        if ($content === false) {
            $io->error("Could not read log file: {$logFile}");

            return null;
        }

        $pattern = '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] [^\n]*\.ERROR: (.+?)(?=\n\[\d{4}-\d{2}-\d{2}|\Z)/s';

        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER) === false) {
            $io->warning('No exceptions found in log file.');

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
     * Extract message, file, and line from a raw log entry string.
     *
     * @return array{message: string, file: string, line: int}
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

        return ['message' => $message, 'file' => $file, 'line' => $line];
    }

    /**
     * Analyze all (or limited) exceptions in a log file and output batch results.
     */
    private function handleBatch(string $logFile, InputInterface $input, SymfonyStyle $io): int
    {
        $entries = $this->parseAllExceptionsFromLog($logFile, $io);
        if ($entries === null || $entries === []) {
            return Command::FAILURE;
        }

        $limitOpt = $input->getOption('limit');
        $limit = 10;
        if ($limitOpt !== null) {
            if (is_int($limitOpt)) {
                $limit = $limitOpt;
            } elseif (is_string($limitOpt) && is_numeric($limitOpt)) {
                $limit = (int) $limitOpt;
            }
        }
        $limit = $limit < 1 ? 10 : $limit;

        $toAnalyze = array_slice($entries, -$limit);

        $explanations = [];
        foreach ($toAnalyze as $entry) {
            $explanations[] = $this->analyzer->analyzeFromLog(
                $entry['message'],
                $entry['file'],
                $entry['line'],
            );
        }

        $this->outputBatchExplanations($explanations, $input, $io);

        return Command::SUCCESS;
    }

    /**
     * Output multiple explanations (batch mode).
     *
     * @param array<int, Explanation> $explanations
     */
    private function outputBatchExplanations(array $explanations, InputInterface $input, SymfonyStyle $io): void
    {
        $format = $input->getOption('format');
        $format = is_string($format) ? $format : 'text';
        $renderer = RendererFactory::forFormat($format);

        $count = count($explanations);
        foreach ($explanations as $i => $explanation) {
            if ($count > 1) {
                $io->writeln('');
                $io->writeln('--- Exception ' . ($i + 1) . ' / ' . $count . ' ---');
                $io->writeln('');
            }
            $io->writeln($renderer->render($explanation));
        }
    }

    /**
     * Output the explanation in the requested format.
     */
    private function outputExplanation(Explanation $explanation, InputInterface $input, SymfonyStyle $io): void
    {
        $format = $input->getOption('format');
        $renderer = RendererFactory::forFormat(is_string($format) ? $format : 'text');
        $io->writeln($renderer->render($explanation));
    }
}
