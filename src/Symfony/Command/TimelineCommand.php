<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Symfony\Command;

use ClarityPHP\RuntimeInsight\Contracts\LogParserInterface;
use ClarityPHP\RuntimeInsight\Log\TimelineService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function is_readable;
use function is_string;
use function sprintf;

#[AsCommand(
    name: 'runtime:timeline',
    description: 'Show runtime timeline: events before the last failure (default: var/log/dev.log)',
)]
final class TimelineCommand extends Command
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
            ->addOption('last', 'l', InputOption::VALUE_REQUIRED, 'Number of last log entries to include in timeline', '20');
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

        $last = $input->getOption('last');
        $lastN = is_numeric($last) ? (int) $last : 20;
        $lastN = $lastN < 1 ? 20 : $lastN;

        $service = new TimelineService($this->parser, $lastN);
        $result = $service->buildFromLog($logPath);

        if ($result->isEmpty()) {
            $io->warning('No events found in log file (or file is empty).');

            return Command::SUCCESS;
        }

        $io->title('Runtime Timeline (last failure)');
        foreach ($result->events as $event) {
            $io->writeln(sprintf(
                'T+%.2fs   %-16s %s',
                $event->relativeSeconds,
                $event->label,
                $event->detail !== '' ? $event->detail : '',
            ));
        }

        return Command::SUCCESS;
    }
}
