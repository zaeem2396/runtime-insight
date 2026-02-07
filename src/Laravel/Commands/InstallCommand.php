<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Laravel\Commands;

use Illuminate\Console\Command;

use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function str_contains;

/**
 * Add OPEN_AI_APIKEY to .env if missing (so user can fill it in).
 */
final class InstallCommand extends Command
{
    protected $signature = 'runtime:install';

    protected $description = 'Add OPEN_AI_APIKEY to .env if not present (for OpenAI as default AI provider)';

    public function handle(): int
    {
        $path = base_path('.env');

        if (! file_exists($path)) {
            $this->warn('No .env file found. Create one and add: OPEN_AI_APIKEY=');

            return self::SUCCESS;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            $this->error('Could not read .env file.');

            return self::FAILURE;
        }

        if (str_contains($content, 'OPEN_AI_APIKEY')) {
            $this->info('OPEN_AI_APIKEY is already in your .env file.');

            return self::SUCCESS;
        }

        $append = "\n# Runtime Insight (OpenAI)\nOPEN_AI_APIKEY=\n";
        $newContent = $content . $append;

        if (file_put_contents($path, $newContent) === false) {
            $this->error('Could not write to .env file.');

            return self::FAILURE;
        }

        $this->info('Added OPEN_AI_APIKEY= to .env. Set your OpenAI API key there and run `php artisan runtime:doctor` to verify.');

        return self::SUCCESS;
    }
}
