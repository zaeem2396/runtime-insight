<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Symfony\Command;

use ClarityPHP\RuntimeInsight\Contracts\LogParserInterface;
use ClarityPHP\RuntimeInsight\Log\LogAnalyzerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function is_readable;
use function sprintf;
use function strlen;
use function substr;

#[AsCommand(
    name: 'runtime:analyze',
    description: 'Analyze log file: summarize error types and counts, highlight top failures (default: var/log/dev.log)',
)]
final class AnalyzeCommand extends Command
{
    public function __construct(
        private readonly LogParserInterface $parser,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('log', InputArgument::OPTIONAL, 'Path to log file')
            ->addOption('top', 't', InputOption::VALUE_REQUIRED, 'Number of top failures to show', '10');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $logArg = $input->getArgument('log');
        $logPath = is_string($logArg) && $logArg !== '' ? $logArg : 'var/log/dev.log';

        if (! is_readable($logPath)) {
            $io->error("Log file not found or not readable: {$logPath}");

            return Command::FAILURE;
        }

        $top = $input->getOption('top');
        $topLimit = is_numeric($top) ? (int) $top : 10;
        $topLimit = $topLimit < 1 ? 10 : $topLimit;

        $service = new LogAnalyzerService($this->parser, $topLimit);
        $result = $service->analyze($logPath);

        if ($result->isEmpty()) {
            $io->warning('No errors found in log file.');

            return Command::SUCCESS;
        }

        $io->title('Runtime Insight — Log Analysis: ' . $result->logPath);
        $io->writeln('Total: ' . $result->totalErrors . ' error(s)');
        $io->newLine();

        if ($result->topFailures !== []) {
            $io->section('Top failures');
            foreach ($result->topFailures as $i => $row) {
                $sample = $row['sample'];
                $msg = $sample->message;
                if (strlen($msg) > 55) {
                    $msg = substr($msg, 0, 52) . '...';
                }
                $io->writeln(sprintf(
                    '  %d. %s (%s) — %d',
                    $i + 1,
                    $sample->exceptionClass . ' ' . $msg,
                    $sample->file . ':' . $sample->line,
                    $row['count'],
                ));
            }
            $io->newLine();
        }

        $io->writeln('Run: php bin/console runtime:explain --log=' . $result->logPath . ' --line=<n> for details.');

        return Command::SUCCESS;
    }
}
