<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Tests\Feature\Symfony\Commands;

use ClarityPHP\RuntimeInsight\Config;
use ClarityPHP\RuntimeInsight\Contracts\AnalyzerInterface;
use ClarityPHP\RuntimeInsight\DTO\Explanation;
use ClarityPHP\RuntimeInsight\Symfony\Command\DoctorCommand;
use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

final class DoctorCommandTest extends TestCase
{
    private Config $config;
    private AnalyzerInterface $analyzer;
    private DoctorCommand $command;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new Config(
            enabled: true,
            aiEnabled: true,
            aiApiKey: 'test-key',
        );

        $this->analyzer = $this->createMock(AnalyzerInterface::class);

        $application = new Application();
        $this->command = new DoctorCommand($this->config, $this->analyzer);
        $application->add($this->command);

        $this->commandTester = new CommandTester($this->command);
    }

    public function test_it_runs_all_checks_successfully(): void
    {
        $explanation = new Explanation(
            message: 'Test',
            cause: 'Test cause',
            suggestions: [],
            confidence: 0.5,
        );

        $this->analyzer
            ->method('analyze')
            ->willReturn($explanation);

        $this->commandTester->execute([]);

        $this->assertSame(0, $this->commandTester->getStatusCode());
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('All checks passed', $output);
        $this->assertStringContainsString('Runtime Insight is enabled', $output);
        $this->assertStringContainsString('Configuration is valid', $output);
        $this->assertStringContainsString('Analyzer is working', $output);
        $this->assertStringContainsString('AI provider is configured', $output);
    }

    public function test_it_reports_when_disabled(): void
    {
        $config = new Config(
            enabled: false,
        );

        $application = new Application();
        $command = new DoctorCommand($config, $this->analyzer);
        $application->add($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(1, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Runtime Insight is disabled', $output);
    }

    public function test_it_reports_when_ai_key_missing(): void
    {
        $config = new Config(
            enabled: true,
            aiEnabled: true,
            aiApiKey: null,
        );

        $explanation = new Explanation(
            message: 'Test',
            cause: 'Test cause',
            suggestions: [],
            confidence: 0.5,
        );

        $this->analyzer
            ->method('analyze')
            ->willReturn($explanation);

        $application = new Application();
        $command = new DoctorCommand($config, $this->analyzer);
        $application->add($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        // When AI is configured to be enabled but key is missing, the command should fail
        $this->assertSame(1, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('AI API key is not configured', $output);
    }

    public function test_it_reports_analyzer_errors(): void
    {
        $this->analyzer
            ->method('analyze')
            ->willThrowException(new Exception('Analyzer error'));

        $this->commandTester->execute([]);

        $this->assertSame(1, $this->commandTester->getStatusCode());
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Analyzer error', $output);
    }
}

