<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Symfony\Command;

use ClarityPHP\RuntimeInsight\Config;
use ClarityPHP\RuntimeInsight\Contracts\AnalyzerInterface;
use ClarityPHP\RuntimeInsight\DTO\Explanation;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

/**
 * Console command to validate Runtime Insight setup.
 */
#[AsCommand(
    name: 'runtime:doctor',
    description: 'Validate Runtime Insight setup and configuration',
)]
final class DoctorCommand extends Command
{
    public function __construct(
        private readonly Config $config,
        private readonly AnalyzerInterface $analyzer,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('🔍 Runtime Insight Diagnostics');
        $io->newLine();

        $allPassed = true;

        // Check if enabled
        if (! $this->checkEnabled($io)) {
            $allPassed = false;
        }

        // Check configuration
        if (! $this->checkConfiguration($io)) {
            $allPassed = false;
        }

        // Check analyzer
        if (! $this->checkAnalyzer($io)) {
            $allPassed = false;
        }

        // Check AI provider (if configured to be enabled)
        if ($this->config->isAIConfigured() && ! $this->checkAIProvider($io)) {
            $allPassed = false;
        }

        $io->newLine();

        if ($allPassed) {
            $io->success('All checks passed! Runtime Insight is properly configured.');

            return Command::SUCCESS;
        }

        $io->warning('Some checks failed. Please review the issues above.');

        return Command::FAILURE;
    }

    /**
     * Check if Runtime Insight is enabled.
     */
    private function checkEnabled(SymfonyStyle $io): bool
    {
        $io->text('Checking if Runtime Insight is enabled...');

        if ($this->config->isEnabled()) {
            $io->success('  ✅ Runtime Insight is enabled');

            return true;
        }

        $io->error('  ❌ Runtime Insight is disabled');
        $io->text('     Enable it in config/packages/runtime_insight.yaml or set runtime_insight.enabled: true');

        return false;
    }

    /**
     * Check configuration validity.
     */
    private function checkConfiguration(SymfonyStyle $io): bool
    {
        $io->text('Checking configuration...');

        $issues = [];

        if ($this->config->getSourceLines() < 1) {
            $issues[] = 'source_lines must be at least 1';
        }

        if ($this->config->getAITimeout() < 1) {
            $issues[] = 'AI timeout must be at least 1 second';
        }

        if ($issues !== []) {
            $io->error('  ❌ Configuration issues found:');
            foreach ($issues as $issue) {
                $io->text("     - {$issue}");
            }

            return false;
        }

        $io->success('  ✅ Configuration is valid');
        $io->text("     Source lines: {$this->config->getSourceLines()}");
        $io->text('     Include request: ' . ($this->config->shouldIncludeRequest() ? 'Yes' : 'No'));
        $io->text('     Sanitize inputs: ' . ($this->config->shouldSanitizeInputs() ? 'Yes' : 'No'));

        return true;
    }

    /**
     * Check if analyzer is working.
     */
    private function checkAnalyzer(SymfonyStyle $io): bool
    {
        $io->text('Checking analyzer...');

        try {
            $testException = new Exception('Test exception for diagnostics');
            $explanation = $this->analyzer->analyze($testException);

            if ($explanation->isEmpty()) {
                $io->warning('  ⚠️  Analyzer returned empty explanation (may be disabled)');

                return true; // Not a failure, just a warning
            }

            $io->success('  ✅ Analyzer is working');
            $io->text("     Test explanation confidence: {$explanation->getConfidence()}");

            return true;
        } catch (Throwable $e) {
            $io->error('  ❌ Analyzer error: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Check AI provider configuration.
     */
    private function checkAIProvider(SymfonyStyle $io): bool
    {
        $io->text('Checking AI provider...');

        $apiKey = $this->config->getAIApiKey();
        if ($apiKey === null || $apiKey === '') {
            $io->error('  ❌ AI API key is not configured');
            $io->text('     Set runtime_insight.ai.api_key in your configuration');

            return false;
        }

        $provider = $this->config->getAIProvider();
        $model = $this->config->getAIModel();

        $io->success('  ✅ AI provider is configured');
        $io->text("     Provider: {$provider}");
        $io->text("     Model: {$model}");
        $io->text("     Timeout: {$this->config->getAITimeout()}s");

        return true;
    }
}

