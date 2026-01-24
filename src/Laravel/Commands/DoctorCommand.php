<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Laravel\Commands;

use ClarityPHP\RuntimeInsight\Config;
use ClarityPHP\RuntimeInsight\Contracts\AnalyzerInterface;
use Illuminate\Console\Command;

/**
 * Artisan command to validate Runtime Insight setup.
 */
final class DoctorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'runtime:doctor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate Runtime Insight setup and configuration';

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
        $this->info('🔍 Runtime Insight Diagnostics');
        $this->newLine();

        $allPassed = true;

        // Check if enabled
        if (! $this->checkEnabled()) {
            $allPassed = false;
        }

        // Check configuration
        if (! $this->checkConfiguration()) {
            $allPassed = false;
        }

        // Check analyzer
        if (! $this->checkAnalyzer()) {
            $allPassed = false;
        }

        // Check AI provider (if enabled)
        if ($this->config->isAIEnabled() && ! $this->checkAIProvider()) {
            $allPassed = false;
        }

        $this->newLine();

        if ($allPassed) {
            $this->info('✅ All checks passed! Runtime Insight is properly configured.');

            return self::SUCCESS;
        }

        $this->warn('⚠️  Some checks failed. Please review the issues above.');

        return self::FAILURE;
    }

    /**
     * Check if Runtime Insight is enabled.
     */
    private function checkEnabled(): bool
    {
        $this->line('Checking if Runtime Insight is enabled...');

        if ($this->config->isEnabled()) {
            $this->info('  ✅ Runtime Insight is enabled');

            return true;
        }

        $this->error('  ❌ Runtime Insight is disabled');
        $this->line('     Enable it in config/runtime-insight.php or set RUNTIME_INSIGHT_ENABLED=true');

        return false;
    }

    /**
     * Check configuration validity.
     */
    private function checkConfiguration(): bool
    {
        $this->line('Checking configuration...');

        $issues = [];

        if ($this->config->getSourceLines() < 1) {
            $issues[] = 'source_lines must be at least 1';
        }

        if ($this->config->getAITimeout() < 1) {
            $issues[] = 'AI timeout must be at least 1 second';
        }

        if ($issues !== []) {
            $this->error('  ❌ Configuration issues found:');
            foreach ($issues as $issue) {
                $this->line("     - {$issue}");
            }

            return false;
        }

        $this->info('  ✅ Configuration is valid');
        $this->line("     Source lines: {$this->config->getSourceLines()}");
        $this->line("     Include request: " . ($this->config->shouldIncludeRequest() ? 'Yes' : 'No'));
        $this->line("     Sanitize inputs: " . ($this->config->shouldSanitizeInputs() ? 'Yes' : 'No'));

        return true;
    }

    /**
     * Check if analyzer is working.
     */
    private function checkAnalyzer(): bool
    {
        $this->line('Checking analyzer...');

        try {
            $testException = new \Exception('Test exception for diagnostics');
            $explanation = $this->analyzer->analyze($testException);

            if ($explanation->isEmpty()) {
                $this->warn('  ⚠️  Analyzer returned empty explanation (may be disabled)');

                return true; // Not a failure, just a warning
            }

            $this->info('  ✅ Analyzer is working');
            $this->line("     Test explanation confidence: {$explanation->getConfidence()}");

            return true;
        } catch (\Throwable $e) {
            $this->error('  ❌ Analyzer error: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Check AI provider configuration.
     */
    private function checkAIProvider(): bool
    {
        $this->line('Checking AI provider...');

        $apiKey = $this->config->getAIApiKey();
        if ($apiKey === null || $apiKey === '') {
            $this->error('  ❌ AI API key is not configured');
            $this->line('     Set RUNTIME_INSIGHT_AI_KEY in your .env file');

            return false;
        }

        $provider = $this->config->getAIProvider();
        $model = $this->config->getAIModel();

        $this->info('  ✅ AI provider is configured');
        $this->line("     Provider: {$provider}");
        $this->line("     Model: {$model}");
        $this->line("     Timeout: {$this->config->getAITimeout()}s");

        // Note: We don't actually test the API connection here to avoid
        // making unnecessary API calls during diagnostics

        return true;
    }
}

