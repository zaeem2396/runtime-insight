# Usage Guide

This guide covers all usage scenarios for ClarityPHP Runtime Insight.

---

## Table of Contents

- [Quick Start](#quick-start)
- [Laravel Integration](#laravel-integration)
- [Symfony Integration](#symfony-integration)
- [Artisan Commands](#artisan-commands-laravel)
- [Console Commands](#console-commands-symfony)
- [Configuration Options](#configuration-options)
- [AI Provider Configuration](#ai-provider-configuration)
- [Custom Integrations](#custom-integrations)
- [Production Considerations](#production-considerations)

---

## Quick Start

### Installation

```bash
composer require clarityphp/runtime-insight
```

### Basic Configuration

After installation, create your configuration file and set up your AI provider API key:

```bash
# Laravel
php artisan vendor:publish --tag=runtime-insight-config

# Set your API key in .env
RUNTIME_INSIGHT_AI_KEY=your-api-key-here
```

That's it! Runtime Insight will now automatically analyze exceptions and provide explanations.

---

## Laravel Integration

### Service Provider

The package auto-registers via Laravel's package discovery. If you need manual registration:

```php
// config/app.php
'providers' => [
    // ...
    ClarityPHP\RuntimeInsight\Laravel\RuntimeInsightServiceProvider::class,
],
```

### Exception Handler Integration

Runtime Insight can automatically analyze exceptions. Use the provided trait for easy integration:

```php
// app/Exceptions/Handler.php
use ClarityPHP\RuntimeInsight\Laravel\Traits\HandlesRuntimeInsight;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    use HandlesRuntimeInsight;

    public function report(Throwable $e): void
    {
        $this->analyzeWithRuntimeInsight($e);
        
        parent::report($e);
    }
}
```

**Or manually use the Facade:**

```php
use ClarityPHP\RuntimeInsight\Facades\RuntimeInsight;

class Handler extends ExceptionHandler
{
    public function report(Throwable $e): void
    {
        RuntimeInsight::analyze($e);
        
        parent::report($e);
    }
}
```

**Automatic Logging:**

When exceptions are analyzed, explanations are automatically logged to Laravel's log at the `debug` level. Check your logs for entries like:

```
[2026-01-21 10:00:00] local.DEBUG: Runtime Insight Explanation {"exception":"TypeError",...}
```

### Facade Usage

```php
use ClarityPHP\RuntimeInsight\Facades\RuntimeInsight;

// Analyze an exception
$explanation = RuntimeInsight::analyze($exception);

// Get explanation text
echo $explanation->getMessage();

// Get suggested fixes
foreach ($explanation->getSuggestions() as $suggestion) {
    echo "- {$suggestion}\n";
}

// Get confidence score
echo "Confidence: {$explanation->getConfidence()}";
```

---

## Symfony Integration

### Bundle Registration

```php
// config/bundles.php
return [
    // ...
    ClarityPHP\RuntimeInsight\Symfony\RuntimeInsightBundle::class => ['all' => true],
];
```

### Configuration

```yaml
# config/packages/runtime_insight.yaml
runtime_insight:
    enabled: true
    
    ai:
        enabled: true
        provider: openai
        model: gpt-4.1-mini
        api_key: '%env(RUNTIME_INSIGHT_AI_KEY)%'
        timeout: 5
    
    context:
        source_lines: 10
        include_request: true
        sanitize_inputs: true
    
    environments:
        - dev
        - staging
```

### Event Subscriber

Runtime Insight automatically subscribes to `KernelEvents::EXCEPTION` and analyzes exceptions. Explanations are logged to Symfony's logger at the `debug` level.

For custom handling, you can inject the analyzer service:

```php
use ClarityPHP\RuntimeInsight\Contracts\AnalyzerInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

class CustomExceptionHandler
{
    public function __construct(
        private AnalyzerInterface $analyzer,
    ) {}

    public function onKernelException(ExceptionEvent $event): void
    {
        $explanation = $this->analyzer->analyze(
            $event->getThrowable()
        );
        
        // Log or display the explanation
        if ($explanation->hasExplanation()) {
            // Your custom handling
        }
    }
}
```

---

## Artisan Commands (Laravel)

#### `runtime:explain`

Explains the most recent runtime error or a specific log entry.

```bash
# Explain the last error (when available)
php artisan runtime:explain

# Explain from a specific log file and line
php artisan runtime:explain --log=storage/logs/laravel.log --line=243

# Output as JSON
php artisan runtime:explain --format=json

# Output as Markdown
php artisan runtime:explain --format=markdown
```

**Options:**

| Option | Description | Default |
|--------|-------------|---------|
| `--log` | Path to log file | None (searches for last exception) |
| `--line` | Line number in log file | Last exception |
| `--format` | Output format (text, json, markdown) | text |

**Example Output:**

```
❗ Runtime Error Explained

Error:
  Call to a member function getName() on null

Why this happened:
  You tried to call the method `getName()` on a variable that is null.
  A variable that was expected to contain an object is actually null.

Where:
  app/Http/Controllers/UserController.php:42

Suggested Fix:
  - Check if the variable is null before accessing it using `if ($variable !== null)`
  - Use the null coalescing operator `??` to provide a default value
  - Use the nullsafe operator `?->` for optional chaining (PHP 8+)

Confidence: 0.85
```

#### `runtime:doctor`

Validates the package setup and configuration.

```bash
php artisan runtime:doctor
```

**Checks performed:**

- ✅ Runtime Insight enabled status
- ✅ Configuration validity
- ✅ Analyzer functionality
- ✅ AI provider configuration (if enabled)

**Example Output:**

```
🔍 Runtime Insight Diagnostics

Checking if Runtime Insight is enabled...
  ✅ Runtime Insight is enabled
Checking configuration...
  ✅ Configuration is valid
     Source lines: 10
     Include request: Yes
     Sanitize inputs: Yes
Checking analyzer...
  ✅ Analyzer is working
     Test explanation confidence: 0.85

✅ All checks passed! Runtime Insight is properly configured.
```

---

## Console Commands (Symfony)

### `runtime:explain`

Explains the most recent runtime error or a specific log entry.

```bash
# Explain the last exception
php bin/console runtime:explain

# With specific log file
php bin/console runtime:explain --log=var/log/dev.log

# With line number
php bin/console runtime:explain --log=var/log/dev.log --line=243

# JSON output
php bin/console runtime:explain --format=json

# Markdown output
php bin/console runtime:explain --format=markdown
```

**Options:**

| Option | Description | Default |
|--------|-------------|---------|
| `--log` | Path to log file | None (searches for last exception) |
| `--line` | Line number in log file | Last exception |
| `--format` | Output format (text, json, markdown) | text |

### `runtime:doctor`

Validates the package setup and configuration.

```bash
php bin/console runtime:doctor
```

**Checks performed:**

- ✅ Runtime Insight enabled status
- ✅ Configuration validity
- ✅ Analyzer functionality
- ✅ AI provider configuration (if enabled)

**Example Output:**

```
🔍 Runtime Insight Diagnostics

Checking if Runtime Insight is enabled...
  ✅ Runtime Insight is enabled
Checking configuration...
  ✅ Configuration is valid
     Source lines: 10
     Include request: Yes
     Sanitize inputs: Yes
Checking analyzer...
  ✅ Analyzer is working
     Test explanation confidence: 0.85
Checking AI provider...
  ✅ AI provider is configured
     Provider: openai
     Model: gpt-4.1-mini
     Timeout: 5s

✅ All checks passed! Runtime Insight is properly configured.
```

---

## Configuration Options

### Full Configuration Reference

```php
// config/runtime-insight.php
return [
    /*
    |--------------------------------------------------------------------------
    | Enable/Disable Package
    |--------------------------------------------------------------------------
    */
    'enabled' => env('RUNTIME_INSIGHT_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | AI Configuration
    |--------------------------------------------------------------------------
    */
    'ai' => [
        // Enable AI-powered analysis
        'enabled' => env('RUNTIME_INSIGHT_AI_ENABLED', true),
        
        // AI provider: openai, anthropic, ollama
        'provider' => env('RUNTIME_INSIGHT_AI_PROVIDER', 'openai'),
        
        // Model to use
        'model' => env('RUNTIME_INSIGHT_AI_MODEL', 'gpt-4.1-mini'),
        
        // API key (provider-specific)
        'api_key' => env('RUNTIME_INSIGHT_AI_KEY'),
        
        // Request timeout in seconds
        'timeout' => env('RUNTIME_INSIGHT_AI_TIMEOUT', 5),
        
        // Maximum tokens for response
        'max_tokens' => env('RUNTIME_INSIGHT_AI_MAX_TOKENS', 1000),
        
        // Base URL (for self-hosted models)
        'base_url' => env('RUNTIME_INSIGHT_AI_BASE_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Context Collection
    |--------------------------------------------------------------------------
    */
    'context' => [
        // Lines of source code to include around error
        'source_lines' => 10,
        
        // Include HTTP request context
        'include_request' => true,
        
        // Include route/controller information
        'include_route' => true,
        
        // Include authenticated user info
        'include_user' => true,
        
        // Sanitize sensitive input data
        'sanitize_inputs' => true,
        
        // Fields to always redact
        'redact_fields' => [
            'password',
            'password_confirmation',
            'credit_card',
            'cvv',
            'ssn',
            'token',
            'secret',
            'api_key',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Environment Settings
    |--------------------------------------------------------------------------
    */
    // Environments where Runtime Insight is active
    'environments' => ['local', 'staging'],
    
    // Never run in these environments
    'disabled_environments' => ['production'],

    /*
    |--------------------------------------------------------------------------
    | Output Configuration
    |--------------------------------------------------------------------------
    */
    'output' => [
        // Default output channel: log, console, both
        'channel' => 'log',
        
        // Log channel to use (Laravel)
        'log_channel' => env('RUNTIME_INSIGHT_LOG_CHANNEL', 'stack'),
        
        // Log level for explanations
        'log_level' => 'debug',
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    */
    'cache' => [
        // Cache identical error explanations
        'enabled' => true,
        
        // Cache TTL in seconds
        'ttl' => 3600,
        
        // Cache store to use
        'store' => env('RUNTIME_INSIGHT_CACHE_STORE', 'file'),
    ],
];
```

---

## AI Provider Configuration

### OpenAI

The OpenAI provider is now fully integrated and automatically used when configured.

**Configuration:**

```php
// config/runtime-insight.php (Laravel)
'ai' => [
    'enabled' => true,
    'provider' => 'openai',
    'model' => 'gpt-4.1-mini',  // or gpt-4.1, gpt-4o, gpt-4-turbo
    'api_key' => env('RUNTIME_INSIGHT_AI_KEY'),
    'timeout' => 5,  // seconds
],
```

```yaml
# config/packages/runtime_insight.yaml (Symfony)
runtime_insight:
    ai:
        enabled: true
        provider: openai
        model: gpt-4.1-mini
        api_key: '%env(RUNTIME_INSIGHT_AI_KEY)%'
        timeout: 5
```

**Features:**
- Automatic retry with exponential backoff for rate limits (429 errors)
- Token usage tracking in explanation metadata
- JSON and text response parsing
- Configurable timeout
- Error handling and logging

**How it works:**
1. Rule-based strategies are tried first (fast, free)
2. If no strategy matches and AI is enabled, OpenAI is called
3. The AI analyzes the error context and returns an explanation
4. Token usage is tracked for monitoring

The active provider is chosen from config (`ai.provider`) and instantiated via `ProviderFactory` (used by `RuntimeInsightFactory::createAIProvider`). You can create a provider directly with `ProviderFactory::createProvider($config)`.

### Anthropic (Claude) *(planned for v0.5.0)*

Config structure for when Claude support is added:

```php
'ai' => [
    'provider' => 'anthropic',
    'model' => 'claude-sonnet-4-20250514',
    'api_key' => env('ANTHROPIC_API_KEY'),
],
```

### Ollama (Local) *(planned for v0.5.0)*

Config structure for when Ollama support is added:

```php
'ai' => [
    'provider' => 'ollama',
    'model' => 'llama3.2',
    'base_url' => 'http://localhost:11434',
],
```

### Custom Provider

```php
use ClarityPHP\RuntimeInsight\Contracts\AIProviderInterface;

class MyCustomProvider implements AIProviderInterface
{
    public function analyze(RuntimeContext $context): Explanation
    {
        // Your implementation
    }
    
    public function isAvailable(): bool
    {
        return true;
    }
}

// Register in service provider
$this->app->bind(AIProviderInterface::class, MyCustomProvider::class);
```

---

## Custom Integrations

### Custom Explanation Strategies

Add domain-specific error patterns:

```php
use ClarityPHP\RuntimeInsight\Contracts\ExplanationStrategyInterface;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use ClarityPHP\RuntimeInsight\DTO\Explanation;

class PaymentErrorStrategy implements ExplanationStrategyInterface
{
    public function supports(RuntimeContext $context): bool
    {
        return str_contains(
            $context->exception->getMessage(),
            'payment'
        );
    }
    
    public function explain(RuntimeContext $context): Explanation
    {
        return new Explanation(
            message: 'Payment processing failed',
            cause: 'The payment gateway returned an error',
            suggestions: [
                'Check payment gateway credentials',
                'Verify the card details are correct',
                'Ensure sufficient funds are available',
            ],
            confidence: 0.95,
        );
    }
    
    public function priority(): int
    {
        return 100; // Higher priority = checked first
    }
}
```

### Custom Output Renderers

```php
use ClarityPHP\RuntimeInsight\Contracts\RendererInterface;
use ClarityPHP\RuntimeInsight\DTO\Explanation;

class SlackRenderer implements RendererInterface
{
    public function render(Explanation $explanation): void
    {
        // Send to Slack webhook
        Http::post(config('services.slack.webhook'), [
            'text' => $this->formatForSlack($explanation),
        ]);
    }
}
```

---

## Production Considerations

### Recommended Production Config

```php
return [
    'enabled' => false,  // Disable in production by default
    
    'ai' => [
        'enabled' => false,
    ],
    
    'environments' => ['local', 'staging'],
    'disabled_environments' => ['production'],
];
```

### Selective Production Usage

If you need Runtime Insight in production for specific scenarios:

```php
return [
    'enabled' => true,
    
    'ai' => [
        'enabled' => true,
        'timeout' => 2,  // Shorter timeout
    ],
    
    'context' => [
        'include_request' => false,  // Don't capture request data
        'include_user' => false,     // Don't capture user data
        'sanitize_inputs' => true,
    ],
    
    'output' => [
        'channel' => 'log',  // Only log, don't display
    ],
];
```

### Performance Tips

1. **Use caching** - Enable explanation caching for repeated errors
2. **Short timeouts** - Keep AI timeout low (2-5 seconds)
3. **Rule-based first** - Many common errors don't need AI
4. **Async processing** - Consider queue-based analysis for production

---

## Troubleshooting

### Common Issues

**"AI provider not responding"**
```bash
php artisan runtime:doctor
```
Check API key and network connectivity.

**"Explanation not appearing"**
- Verify the environment is in the `environments` list
- Check that `enabled` is `true`
- Review log files for errors

**"Rate limiting errors"**
- Enable caching to reduce API calls
- Use a larger model with higher rate limits
- Consider Ollama for unlimited local inference

---

## Next Steps

- Read the [API Reference](docs/api.md) for programmatic usage
- Check [Examples](docs/examples/) for common use cases
- Join our [Discord](https://discord.gg/clarityphp) for support

