<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Symfony\Command;

use ClarityPHP\RuntimeInsight\Contracts\AnalyzerInterface;
use ClarityPHP\RuntimeInsight\DTO\Explanation;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

use function count;
use function file_exists;
use function file_get_contents;
use function is_int;
use function is_readable;
use function is_string;
use function json_encode;
use function preg_match_all;

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
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'Output format (text, json, markdown)', 'text');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $throwable = $this->getException($input, $io);

        if ($throwable === null) {
            $io->error('No exception found to analyze.');

            return Command::FAILURE;
        }

        $explanation = $this->analyzer->analyze($throwable);

        if ($explanation->isEmpty()) {
            $io->warning('Runtime Insight is disabled or no explanation could be generated.');

            return Command::SUCCESS;
        }

        $this->outputExplanation($explanation, $input, $io);

        return Command::SUCCESS;
    }

    /**
     * Get the exception to analyze.
     */
    private function getException(InputInterface $input, SymfonyStyle $io): ?Throwable
    {
        $logFile = $input->getOption('log');
        $line = $input->getOption('line');

        if ($logFile !== null && is_string($logFile)) {
            $lineNumber = null;
            if ($line !== null) {
                if (is_int($line)) {
                    $lineNumber = $line;
                } elseif (is_string($line) && is_numeric($line)) {
                    $lineNumber = (int) $line;
                }
            }

            return $this->parseExceptionFromLog($logFile, $lineNumber, $io);
        }

        // Try to get from in-memory buffer (if available)
        // For now, return null
        return null;
    }

    /**
     * Parse exception from log file.
     */
    private function parseExceptionFromLog(string $logFile, ?int $lineNumber, SymfonyStyle $io): ?Throwable
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

        // Simple regex to find exception patterns
        $pattern = '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] .*?\.ERROR: (.+?)(?=\[|\Z)/s';

        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER) === false) {
            $io->warning('No exceptions found in log file.');

            return null;
        }

        if ($lineNumber !== null && isset($matches[$lineNumber - 1])) {
            $match = $matches[$lineNumber - 1];
        } else {
            // Get the last exception
            $match = $matches[count($matches) - 1] ?? null;
        }

        if ($match === null) {
            $io->warning('No matching exception found.');

            return null;
        }

        // For now, create a generic exception
        // preg_match guarantees index 2 exists when match succeeds
        /** @phpstan-ignore-next-line */
        return new Exception($match[2] ?? 'Exception from log');
    }

    /**
     * Output the explanation in the requested format.
     */
    private function outputExplanation(Explanation $explanation, InputInterface $input, SymfonyStyle $io): void
    {
        $format = $input->getOption('format');

        match ($format) {
            'json' => $this->outputJson($explanation, $io),
            'markdown' => $this->outputMarkdown($explanation, $io),
            default => $this->outputText($explanation, $io),
        };
    }

    /**
     * Output explanation as formatted text.
     */
    private function outputText(Explanation $explanation, SymfonyStyle $io): void
    {
        $io->title('Runtime Error Explained');
        $io->section('Error');
        $io->text($explanation->getMessage());

        if ($explanation->getCause() !== '') {
            $io->section('Why this happened');
            $io->text($explanation->getCause());
        }

        if ($explanation->getLocation() !== null) {
            $io->section('Where');
            $io->text($explanation->getLocation());
        }

        $suggestions = $explanation->getSuggestions();
        if ($suggestions !== []) {
            $io->section('Suggested Fix');
            $io->listing($suggestions);
        }

        $io->text("Confidence: {$explanation->getConfidence()}");
    }

    /**
     * Output explanation as JSON.
     */
    private function outputJson(Explanation $explanation, SymfonyStyle $io): void
    {
        $json = json_encode($explanation->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $io->writeln($json !== false ? $json : '{}');
    }

    /**
     * Output explanation as Markdown.
     */
    private function outputMarkdown(Explanation $explanation, SymfonyStyle $io): void
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

        $io->writeln($output);
    }
}
