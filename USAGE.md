# Usage Guide

This guide covers all usage scenarios for Runtime Insight. For a shorter overview, see [README.md](README.md). Release history is in [CHANGELOG.md](CHANGELOG.md).

---

## Table of Contents

- [Quick Start](#quick-start)
- [Laravel Integration](#laravel-integration)
- [Symfony Integration](#symfony-integration)
- [Artisan Commands](#artisan-commands-laravel)
- [Console Commands](#console-commands-symfony)
- [Configuration Options](#configuration-options)
- [Caching](#caching)
- [Database query context](#database-query-context)
- [Memory and performance context](#memory-and-performance-context)
- [Root cause analysis](#root-cause-analysis)
- [Events and event dispatcher](#events-and-event-dispatcher)
- [Webhooks (after analysis)](#webhooks-after-analysis)
- [AI Provider Configuration](#ai-provider-configuration)
- [Custom Integrations](#custom-integrations)
- [Production Considerations](#production-considerations)

---

## Quick Start

### Installation

```bash
composer require zaeem2396/runtime-insight
```

### Basic Configuration

After installation, add your OpenAI API key (OpenAI is the default AI provider):

```bash
# Laravel: add OPEN_AI_APIKEY to .env if not present
php artisan runtime:install

# Then set your key in .env
OPEN_AI_APIKEY=your-api-key-here
```

Optional: publish the config file to customize settings:

```bash
php artisan vendor:publish --tag=runtime-insight-config
```

If you run `php artisan runtime:explain` without an API key, you will see: *No OpenAI API key found. Set OPEN_AI_APIKEY or RUNTIME_INSIGHT_AI_KEY in your .env file.*

**Optional integrations:** To hook analysis in PHP, see [Events and event dispatcher](#events-and-event-dispatcher). To POST JSON to external URLs after each explanation, see [Webhooks (after analysis)](#webhooks-after-analysis).

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
        if (! $explanation->isEmpty()) {
            // Your custom handling
        }
    }
}
```

---

## Artisan Commands (Laravel)

#### `runtime:install`

Adds `OPEN_AI_APIKEY=` to your `.env` file if it is not already there. Run once after installing the package so you can set your OpenAI API key. The default AI provider is OpenAI.

```bash
php artisan runtime:install
```

#### `runtime:explain`

Explains the most recent runtime error or a specific log entry.

```bash
# Explain the last error (when available)
php artisan runtime:explain

# Explain from a specific log file and line
php artisan runtime:explain --log=storage/logs/laravel.log --line=243

# Batch: analyze all exceptions in the log (last 10 by default)
php artisan runtime:explain --log=storage/logs/laravel.log --all

# Batch with a custom limit (e.g. last 5 entries)
php artisan runtime:explain --log=storage/logs/laravel.log --all --limit=5

# Output as JSON
php artisan runtime:explain --format=json

# Output as Markdown
php artisan runtime:explain --format=markdown

# Output as HTML (debug view)
php artisan runtime:explain --format=html

# Write explanation to a file
php artisan runtime:explain --log=storage/logs/laravel.log --output=explanation.txt

# Output with IDE-friendly location (file:line on first line)
php artisan runtime:explain --format=ide
```

**Options:**

| Option | Description | Default |
|--------|-------------|---------|
| `--log` | Path to log file | None (searches for last exception) |
| `--line` | Line number in log file | Last exception |
| `--all` | Analyze all exceptions in the log (use with `--log`); use `--limit` to cap how many | Off |
| `--limit` | Max entries to analyze in batch mode (most recent; default 10) | 10 |
| `--format` | Output format (text, json, markdown, html, ide) | text |
| `--output` | Write explanation to file instead of console | None |

**Log-based analysis:** When you use `--log`, the command parses the exception type (e.g. `TypeError`, `ErrorException`) from each log entry. That allows rule-based strategies to match correctly, so you get accurate explanations and suggestions instead of a generic "Exception" fallback.

**Batch analysis:** Use `--all` with `--log` to explain multiple exceptions in one run. The command analyzes the most recent entries first (up to `--limit`, default 10) to avoid excessive API calls. Each explanation is output with a separator (e.g. "Exception 1 / N").

**Export to file:** Use `--output=path` (or `-o path` on Symfony) to write the explanation to a file instead of the console. Works with single and batch mode.

**Code block and call site:** When the error location is in your app (and the file is readable), the output includes a **Code block (to update)** snippet (with line numbers and the error line marked with →) and, when present, **Called from (fix here)** (e.g. when the message says "called in … on line N" or from the stack trace). This makes it clear which code block to change and where the bad value was passed from.

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

Called from (fix here):
  app/Http/Controllers/UserController.php:38

Code block (to update):
  →  42 |   return $user->getName();

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

# Batch: analyze all (or last N) exceptions in the log
php bin/console runtime:explain --log=var/log/dev.log --all
php bin/console runtime:explain --log=var/log/dev.log --all --limit=5

# Write explanation to a file (-o)
php bin/console runtime:explain --log=var/log/dev.log --output=explanation.txt

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
| `--all` | Analyze all exceptions in the log (use with `--log`) | Off |
| `--limit` | Max entries in batch mode (default 10) | 10 |
| `--format` | Output format (text, json, markdown, html, ide) | text |
| `--output`, `-o` | Write explanation to file instead of console | None |

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
        
        // API key: OPEN_AI_APIKEY for OpenAI (default), or RUNTIME_INSIGHT_AI_KEY
        'api_key' => env('OPEN_AI_APIKEY') ?? env('RUNTIME_INSIGHT_AI_KEY'),
        
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

        // Include recent database queries (Laravel: uses DB::getQueryLog())
        'include_database_queries' => env('RUNTIME_INSIGHT_INCLUDE_DATABASE_QUERIES', false),

        // Maximum number of recent queries to capture
        'max_database_queries' => (int) env('RUNTIME_INSIGHT_MAX_DATABASE_QUERIES', 5),

        // Include memory/performance context (peak memory at time of error)
        'include_performance_context' => env('RUNTIME_INSIGHT_INCLUDE_PERFORMANCE_CONTEXT', false),
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
    |
    | Cache explanations for identical errors (same class, message, file, line)
    | to reduce AI API calls. The default implementation is in-memory per request.
    |
    */
    'cache' => [
        // Enable caching of repeated error explanations
        'enabled' => env('RUNTIME_INSIGHT_CACHE_ENABLED', true),

        // Cache TTL in seconds (0 = no expiry within the request)
        'ttl' => env('RUNTIME_INSIGHT_CACHE_TTL', 3600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhooks (after analysis)
    |--------------------------------------------------------------------------
    |
    | POST a JSON payload to each URL when an explanation is ready. Disabled by
    | default. Failures are logged only and never break the failing request.
    |
    */
    'webhooks' => [
        'enabled' => env('RUNTIME_INSIGHT_WEBHOOKS_ENABLED', false),
        'urls' => array_filter(array_map('trim', explode(',', (string) env('RUNTIME_INSIGHT_WEBHOOK_URLS', '')))),
        'timeout' => (int) env('RUNTIME_INSIGHT_WEBHOOK_TIMEOUT', 3),
        'headers' => [
            // 'Authorization' => 'Bearer '.env('RUNTIME_INSIGHT_WEBHOOK_TOKEN'),
        ],
    ],
];
```

### Caching

Explanation caching reduces AI API calls by storing results for identical errors (same exception class, message, file, and line). When caching is enabled, the explanation engine uses an in-memory cache (per request by default) with the configured TTL.

| Option   | Description                          | Default |
|----------|--------------------------------------|---------|
| `enabled` | Enable caching of repeated errors   | `true`  |
| `ttl`     | Time-to-live in seconds (0 = no expiry) | `3600` |

### Database query context

When enabled, Runtime Insight can include recent database queries in the context sent to the AI. This helps explain errors that occur during or after database operations.

**Laravel:** Uses Laravel's query log (`DB::getQueryLog()`). Enable the query log in your app (e.g. in `AppServiceProvider` for local/staging) so that queries are recorded before the exception occurs:

```php
// AppServiceProvider.php (optional – enable when you want query context)
if (app()->environment('local', 'staging')) {
    \Illuminate\Support\Facades\DB::enableQueryLog();
}
```

Then set `context.include_database_queries` to `true` in your Runtime Insight config. The last N queries (up to `max_database_queries`) are included in the AI summary.

| Option                     | Description                          | Default |
|----------------------------|--------------------------------------|---------|
| `include_database_queries` | Include recent queries in context   | `false` |
| `max_database_queries`      | Maximum number of queries to capture | `5`    |

**Symfony:** Database query context is not yet implemented; the option is accepted in config but no queries are captured.

### Memory and performance context

When enabled, Runtime Insight captures memory and performance data at the time of the error and includes it in the context sent to the AI. This helps explain errors that may be related to memory limits or long-running operations.

**What is captured:**

- **Peak memory** – `memory_get_peak_usage(true)` (real memory, in bytes), formatted for the summary (e.g. "12.5 MB").
- **Script runtime** – Reserved for future use (e.g. elapsed time since request start).

**Configuration:**

| Option                      | Description                          | Default |
|-----------------------------|--------------------------------------|---------|
| `include_performance_context` | Include peak memory in context     | `false` |

Set `context.include_performance_context` to `true` in your config (or `RUNTIME_INSIGHT_INCLUDE_PERFORMANCE_CONTEXT=true` in Laravel). The AI summary will then include a "Performance:" section with peak memory.

---

## Root cause analysis

When `RuntimeInsight` runs with a `RootCauseAnalyzerInterface` implementation (the default `Engine\RootCauseAnalyzer` in Laravel and Symfony), the explanation metadata includes a **`root_cause`** object built from the enriched `RuntimeContext`.

### What is analyzed

| Signal | Role |
|--------|------|
| Exception class and message | Primary cause narrative and a **remediation category** (e.g. `type_null`, `sql`, `validation`). |
| Stack frames (`isVendor`) | Counts of vendor vs application frames, first application location, short narrative. |
| Request context | Method, URI, route name when present. |
| Database context | Number of recent queries captured before failure. |

### Metadata shape

Consumers (logs, webhooks, custom renderers) can read `explanation` metadata:

## Events and event dispatcher

Runtime Insight exposes a small **synchronous** event API so you can hook the analysis pipeline without forking the package. Events are plain readonly DTOs; the dispatcher is **in-process only** (not Symfony’s global `EventDispatcher`).

### When events fire

The same ordering applies to **`RuntimeInsight::analyze`**, **`analyzeFromLog`**, and **`analyzeContext`**:

| Order | What happens |
|-------|----------------|
| 1 | Build `RuntimeContext` (context builder). |
| 2 | Run signal collectors (`CollectorRegistry`) when registered. |
| 3 | **`BeforeAnalysisEvent`** — `dispatch()` with the enriched `RuntimeContext`. |
| 4 | Run `ExplanationEngineInterface` → `Explanation`. |
| 5 | Attach root cause and pattern metadata when analyzers are configured. |
| 6 | **`AfterAnalysisEvent`** — `dispatch()` with final `Explanation` and `RuntimeContext`. |
| 7 | Return the `Explanation` to the caller (handler, command, or your code). |

### Types and contracts

| Name | Namespace / class | Role |
|------|---------------------|------|
| Dispatcher contract | `ClarityPHP\RuntimeInsight\Contracts\EventDispatcherInterface` | `addListener(string $eventClass, callable $listener): void`, `dispatch(object $event): void` |
| Default implementation | `ClarityPHP\RuntimeInsight\Event\InMemoryEventDispatcher` | Listeners keyed by concrete event class name |
| Before hook | `ClarityPHP\RuntimeInsight\Event\BeforeAnalysisEvent` | Public `RuntimeContext $context` |
| After hook | `ClarityPHP\RuntimeInsight\Event\AfterAnalysisEvent` | Public `Explanation $explanation`, `RuntimeContext $context` |
| Factory | `ClarityPHP\RuntimeInsight\Event\InMemoryEventDispatcherFactory::create(Config $config, ?\Psr\Log\LoggerInterface $logger)` | Returns a dispatcher; when `webhooks` are enabled and have URLs, registers the built-in webhook listener on `AfterAnalysisEvent` |

Laravel and Symfony register **`EventDispatcherInterface`** as a singleton built through this factory. **`RuntimeInsight`** receives that same instance, so listeners you add are invoked on the real analysis path.

### Laravel: registering listeners

Register in **`boot()`** of a service provider that loads **after** the package provider (default app providers do). Use the **class name** as the first argument to `addListener` (same as `::class`).

```php
use ClarityPHP\RuntimeInsight\Contracts\EventDispatcherInterface;
use ClarityPHP\RuntimeInsight\Event\BeforeAnalysisEvent;
use ClarityPHP\RuntimeInsight\Event\AfterAnalysisEvent;

public function boot(): void
{
    $dispatcher = $this->app->make(EventDispatcherInterface::class);

    $dispatcher->addListener(BeforeAnalysisEvent::class, static function (BeforeAnalysisEvent $e): void {
        // Context is readonly; log or copy data if you need to mutate downstream behaviour elsewhere
    });

    $dispatcher->addListener(AfterAnalysisEvent::class, static function (AfterAnalysisEvent $e): void {
        // Final explanation and context available here; same phase as optional webhooks
    });
}
```

### Symfony: registering listeners

The bundle registers `EventDispatcherInterface` → factory-created `InMemoryEventDispatcher`. In your application code, add a **compiler pass** or a small **service** that runs early and calls `addListener` on the dispatcher **definition**, or retrieve the dispatcher at runtime from the container in `kernel.request` with high priority (before exceptions are handled). Example: inject `EventDispatcherInterface` into your own service and register in the constructor:

```php
use ClarityPHP\RuntimeInsight\Contracts\EventDispatcherInterface;
use ClarityPHP\RuntimeInsight\Event\AfterAnalysisEvent;

final class RuntimeInsightHooks
{
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $dispatcher->addListener(AfterAnalysisEvent::class, $this->onAfter(...));
    }

    public function onAfter(AfterAnalysisEvent $event): void
    {
        // ...
    }
}
```

Register `RuntimeInsightHooks` as a service with `autoconfigure: true` so it is instantiated when the container is built.

### Standalone (`RuntimeInsightFactory`)

`RuntimeInsightFactory::create()` / `createWithConfig()` builds a dispatcher via **`InMemoryEventDispatcherFactory::create($config, null)`** unless you pass a custom **`EventDispatcherInterface`** as the last argument to `createWithConfig()`. To combine custom listeners with webhooks, start from the factory-created dispatcher (or replicate its registration logic) rather than a bare `InMemoryEventDispatcher` unless you also attach the webhook listener yourself.

### Relationship to webhooks

Configurable **webhooks** are implemented as an **`AfterAnalysisEvent`** listener inside `InMemoryEventDispatcherFactory` when `webhooks.enabled` is true and `urls` is non-empty. Your own `AfterAnalysisEvent` listeners run in registration order together with that listener. See [Webhooks (after analysis)](#webhooks-after-analysis).

---

## Webhooks (after analysis)

When enabled, Runtime Insight sends an HTTP **POST** with `Content-Type: application/json` to each configured URL **after** analysis completes (same timing as `AfterAnalysisEvent`). Use this for Slack incoming webhooks, internal alerting, or custom automation.

### Enablement rules

Delivery runs only when **`webhooks.enabled`** is true **and** the **`urls`** list is non-empty after parsing. If either condition fails, no HTTP requests are made.

### Behaviour and HTTP semantics

- Requests execute **inline** in the same PHP process as the failing request or command; use a **short** `timeout` (default **3** seconds) so slow endpoints do not block your app.
- Each URL receives an independent POST; one failure does not skip the others.
- **4xx/5xx** responses and transport errors are logged at **warning** with `Psr\Log\LoggerInterface` and **never** rethrown; they do not alter the original exception or HTTP response.
- **Success** is any HTTP status strictly below **400** (2xx/3xx). The client uses Guzzle with `http_errors => false` so status codes are inspected explicitly.

### Configuration reference

| Key | Laravel (`config/runtime-insight.php`) | Symfony (`runtime_insight.webhooks`) | Purpose |
|-----|----------------------------------------|--------------------------------------|---------|
| `enabled` | `webhooks.enabled` (env: `RUNTIME_INSIGHT_WEBHOOKS_ENABLED`) | `enabled` | Master switch |
| `urls` | `webhooks.urls` (array) or env `RUNTIME_INSIGHT_WEBHOOK_URLS` (comma-separated) | `urls` (list of strings) | Destination endpoints (HTTPS recommended) |
| `timeout` | `webhooks.timeout` (env: `RUNTIME_INSIGHT_WEBHOOK_TIMEOUT`, default 3) | `timeout` (integer, default 3) | Guzzle timeout per request (seconds, minimum 1 internally) |
| `headers` | `webhooks.headers` (associative name → value) | `headers` (map) | Extra headers on every POST (e.g. `Authorization`, custom signing headers) |

**Laravel environment variables (optional):**

| Variable | Maps to | Notes |
|----------|---------|--------|
| `RUNTIME_INSIGHT_WEBHOOKS_ENABLED` | `webhooks.enabled` | Use `true` / `false` strings as typical for `env()` |
| `RUNTIME_INSIGHT_WEBHOOK_URLS` | `webhooks.urls` | Comma-separated; empty string means no URLs |
| `RUNTIME_INSIGHT_WEBHOOK_TIMEOUT` | `webhooks.timeout` | Integer seconds |

**Symfony** (`config/packages/runtime_insight.yaml`):

```yaml
runtime_insight:
    webhooks:
        enabled: false
        urls: []
        timeout: 3
        headers: {}
        # Example:
        # headers:
        #     Authorization: 'Bearer %env(RUNTIME_INSIGHT_WEBHOOK_TOKEN)%'
```

### Payload shape

Top-level JSON object:

| Field | Type | Description |
|-------|------|-------------|
| `event` | string | Always **`runtime_insight.after_analysis`** |
| `exception` | object | `ExceptionInfo::toArray()` — `class`, `message`, `code`, `file`, `line`, optional previous exception fields |
| `explanation` | object | `Explanation::toArray()` — `message`, `cause`, `suggestions`, `confidence`, `error_type`, `location`, `metadata` (includes root cause / pattern when present), `code_snippet`, `call_site_location` |

**Example (truncated):**

```json
{
  "event": "runtime_insight.after_analysis",
  "exception": {
    "class": "TypeError",
    "message": "Argument #1 must be of type string, null given",
    "code": 0,
    "file": "/var/www/app/Http/Controllers/OrderController.php",
    "line": 42
  },
  "explanation": {
    "message": "Type error",
    "cause": "A null value was passed where a string was required.",
    "suggestions": ["Add a null check or default before using the value."],
    "confidence": 0.9,
    "metadata": {}
  }
}
```

### Security and privacy

- Payloads can contain **paths, messages, snippets, and AI metadata**. Do not send them to untrusted URLs; prefer **HTTPS** and **auth headers**.
- Treat webhook endpoints like any other secret-bearing integration: rotate tokens, scope networks, and disable webhooks in environments where outbound HTTP is not allowed.
- Webhooks are **not** a substitute for a dedicated error tracker (Sentry, etc.); they complement local explanation and logging.

### Advanced: custom listeners vs webhooks

Register your own code on **`EventDispatcherInterface`** for full control (see [Events and event dispatcher](#events-and-event-dispatcher)). Built-in webhooks are simply an **`AfterAnalysisEvent`** listener registered inside **`InMemoryEventDispatcherFactory`** when configuration allows delivery.

---

## AI Provider Configuration

### OpenAI (default provider)

OpenAI is the default AI provider. Set your API key in `.env` as `OPEN_AI_APIKEY` (or run `php artisan runtime:install` to add the line, then fill it in).

**Configuration:**

```php
// config/runtime-insight.php (Laravel)
'ai' => [
    'enabled' => true,
    'provider' => 'openai',   // default
    'model' => 'gpt-4.1-mini',  // or gpt-4.1, gpt-4o, gpt-4-turbo
    // api_key is read from OPEN_AI_APIKEY (or RUNTIME_INSIGHT_AI_KEY) in .env
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

**Fallback chain:** Set `ai.fallback` to an array of provider names (e.g. `['anthropic', 'ollama']`). If the primary provider returns an empty explanation (e.g. API error or rate limit), the next provider in the list is tried until one returns a result. See [Fallback chain](#fallback-chain) below.

### Anthropic (Claude)

The Anthropic provider uses the Claude Messages API for error analysis. Set `ai.provider` to `anthropic` and your Anthropic API key.

**Configuration:**

```php
// config/runtime-insight.php (Laravel)
'ai' => [
    'enabled' => true,
    'provider' => 'anthropic',
    'model' => 'claude-sonnet-4-20250514',  // or claude-3-5-haiku-20241022, claude-3-opus-latest, etc.
    'api_key' => env('RUNTIME_INSIGHT_AI_KEY'),  // or ANTHROPIC_API_KEY
    'timeout' => 5,
],
```

```yaml
# config/packages/runtime_insight.yaml (Symfony)
runtime_insight:
    ai:
        enabled: true
        provider: anthropic
        model: claude-sonnet-4-20250514
        api_key: '%env(RUNTIME_INSIGHT_AI_KEY)%'
        timeout: 5
```

**Features:**
- Messages API with system prompt and user message
- Retry with exponential backoff on rate limits (429)
- Token usage tracking (input + output) in explanation metadata
- Same JSON response format as OpenAI for consistent parsing

**How it works:**
1. Rule-based strategies are tried first
2. If no strategy matches and AI is enabled, the Anthropic Messages API is called
3. Claude analyzes the error context and returns an explanation (JSON or fallback text)
4. Token usage is recorded in metadata

### Ollama (Local)

The Ollama provider uses your local Ollama instance for inference. No API key is required. Set `ai.provider` to `ollama` and optionally configure `base_url` if Ollama is not on the default port.

**Configuration:**

```php
// config/runtime-insight.php (Laravel)
'ai' => [
    'enabled' => true,
    'provider' => 'ollama',
    'model' => 'llama3.2',  // or llama3.1, codellama, mistral, etc.
    'base_url' => env('RUNTIME_INSIGHT_AI_BASE_URL', 'http://localhost:11434'),
    'timeout' => 30,  // Local inference can be slower
],
```

```yaml
# config/packages/runtime_insight.yaml (Symfony)
runtime_insight:
    ai:
        enabled: true
        provider: ollama
        model: llama3.2
        base_url: 'http://localhost:11434'
        timeout: 30
```

**Features:**
- No API key required
- Uses Ollama `/api/chat` endpoint
- Configurable base URL for non-default Ollama hosts
- Same JSON/text response handling as other providers

**How it works:**
1. Rule-based strategies are tried first
2. If no strategy matches and AI is enabled, the local Ollama API is called
3. The model analyzes the error context and returns an explanation
4. Ensure Ollama is running (`ollama serve`) and the model is pulled (`ollama pull llama3.2`)

### Fallback chain

You can configure a list of fallback providers. If the primary provider returns an empty explanation (e.g. API error, rate limit, or timeout), the next provider in the list is tried until one returns a result.

**Configuration:**

```php
// config/runtime-insight.php (Laravel)
'ai' => [
    'enabled' => true,
    'provider' => 'openai',
    'api_key' => env('RUNTIME_INSIGHT_AI_KEY'),
    'fallback' => ['anthropic', 'ollama'],  // try these if OpenAI fails
],
```

```yaml
# config/packages/runtime_insight.yaml (Symfony)
runtime_insight:
    ai:
        enabled: true
        provider: openai
        api_key: '%env(RUNTIME_INSIGHT_AI_KEY)%'
        fallback: ['anthropic', 'ollama']
```

**Behaviour:**
1. The primary provider (e.g. OpenAI) is called first.
2. If it returns an empty explanation, the next provider in `fallback` is tried (e.g. Anthropic, then Ollama).
3. The first non-empty explanation is returned.
4. If all providers return empty, an empty explanation is returned.

Only provider names that are supported by `ProviderFactory` (openai, anthropic, ollama) are used; unknown names are skipped. The primary provider is never duplicated in the chain.

### Custom Provider

Implement `AIProviderInterface` and provide `getName()`, `analyze()`, and `isAvailable()`:

```php
use ClarityPHP\RuntimeInsight\Contracts\AIProviderInterface;
use ClarityPHP\RuntimeInsight\DTO\Explanation;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;

class MyCustomProvider implements AIProviderInterface
{
    public function getName(): string
    {
        return 'my_custom';
    }

    public function analyze(RuntimeContext $context): Explanation
    {
        // Your implementation: call your AI service and return an Explanation
        return new Explanation(
            message: '...',
            cause: '...',
            suggestions: [],
            confidence: 0.9,
        );
    }

    public function isAvailable(): bool
    {
        return true;
    }
}

// Register in service provider (Laravel)
$this->app->bind(AIProviderInterface::class, MyCustomProvider::class);
```

---

## Custom Integrations

For outbound HTTP notifications after each explanation, configure [Webhooks (after analysis)](#webhooks-after-analysis) (env vars, payload schema, and security notes are documented there).

For **in-process** hooks (no HTTP), use [Events and event dispatcher](#events-and-event-dispatcher): resolve `ClarityPHP\RuntimeInsight\Contracts\EventDispatcherInterface` from the container and call `addListener(BeforeAnalysisEvent::class, …)` / `addListener(AfterAnalysisEvent::class, …)`. The same dispatcher instance is injected into `RuntimeInsight`, so listeners must be registered before analysis runs (e.g. Laravel `boot()`, Symfony service construction).

### Built-in strategies and descriptive fallback

Runtime Insight supports **all error types** with descriptive explanations ([#25](https://github.com/zaeem2396/runtime-insight/issues/25)):

- **Dedicated strategies** cover: null pointer, undefined index, type error, argument count, class not found, division by zero, parse error, and value error. Each returns a specific cause and suggestions with high confidence.
- **Descriptive fallback:** When no strategy matches (e.g. `RuntimeException`, `InvalidArgumentException`, `LogicException`), the engine returns a **descriptive** cause and suggestions for that exception class instead of a generic “An exception of type X was thrown” message. Unknown exception types also get a short descriptive sentence and location-based suggestions.

So every error gets a clear, actionable explanation whether or not it has a dedicated strategy.

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

### Output formats

The `runtime:explain` command (and Symfony `runtime:explain`) support multiple output formats via `--format`:

| Format    | Description |
|-----------|-------------|
| `text`    | Human-readable console output (default) |
| `json`    | JSON (explanation as structured data) |
| `markdown`| Markdown document |
| `html`    | HTML debug view (styled page) |
| `ide`     | Same as text but with file:line on first line for IDE link detection |

Use `ClarityPHP\RuntimeInsight\Renderer\RendererFactory::forFormat($format)` to get a `RendererInterface` implementation programmatically.

### Custom Output Renderers

```php
use ClarityPHP\RuntimeInsight\Contracts\RendererInterface;
use ClarityPHP\RuntimeInsight\DTO\Explanation;

class SlackRenderer implements RendererInterface
{
    public function render(Explanation $explanation): string
    {
        return $this->formatForSlack($explanation);
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

    // Keep webhooks off in production unless you have a dedicated, secured endpoint
    'webhooks' => [
        'enabled' => false,
        'urls' => [],
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

1. **Use caching** - Enable `cache.enabled` to cache explanations by error signature (class, message, file, line). Repeated identical errors reuse the cached explanation within the TTL.
2. **Short timeouts** - Keep AI timeout low (2-5 seconds) for remote providers.
3. **Rule-based first** - Many common errors don't need AI; built-in strategies run first.
4. **Async processing** - Consider queue-based analysis for production.

---

## Troubleshooting

### Common Issues

**"AI provider not responding"**

When the AI provider fails (timeout, rate limit, or API error) or returns an empty response, the engine automatically falls back to a rule-based explanation so you still get a basic explanation instead of "no explanation could be generated".

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

**"Webhooks not firing"**
- Ensure `webhooks.enabled` is true and `urls` is non-empty (Laravel: `RUNTIME_INSIGHT_WEBHOOK_URLS`)
- Check application logs for warning lines from `GuzzleWebhookSender` (timeouts, 4xx/5xx)
- Remember delivery runs only after a full analysis (same as `AfterAnalysisEvent`)

---

## Next Steps

- See [Configuration Options](#configuration-options), [Webhooks (after analysis)](#webhooks-after-analysis), and [AI Provider Configuration](#ai-provider-configuration) for full setup
- Use `RendererFactory::forFormat()` and `RuntimeInsightFactory::create()` for programmatic usage (see [README](README.md))
- [Open an issue](https://github.com/zaeem2396/runtime-insight/issues) or discussions for support

