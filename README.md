# ClarityPHP Runtime Insight

<p align="center">
  <img src="https://img.shields.io/packagist/php-v/clarityphp/runtime-insight" alt="PHP Version">
  <img src="https://img.shields.io/github/actions/workflow/status/clarityphp/runtime-insight/tests.yml?branch=main" alt="Build Status">
  <img src="https://img.shields.io/packagist/v/clarityphp/runtime-insight" alt="Latest Version">
  <img src="https://img.shields.io/packagist/l/clarityphp/runtime-insight" alt="License">
</p>

**AI-Augmented PHP Runtime Error Analyzer & Explainer** for Laravel and Symfony applications.

Transform cryptic runtime errors into human-readable explanations with actionable fix suggestions.

---

## 🎯 What is Runtime Insight?

Runtime Insight intercepts runtime exceptions and errors in your PHP applications, analyzes them using structured context and AI reasoning, and produces:

- **Plain-English explanations** of what went wrong
- **Root cause analysis** explaining *why* it happened
- **Actionable fix suggestions** to resolve the issue
- **Confidence scores** for AI-generated insights

### The Problem

Typical PHP runtime errors provide:
```
Call to a member function id() on null
```

This tells you **what** failed, but not **why**.

### The Solution

Runtime Insight explains:

```
❗ Runtime Error Explained

Error:
  Call to a member function id() on null

Why this happened:
  The `$user` variable is null because this route can be accessed
  without authentication, but the controller assumes a logged-in user.

Where:
  App\Http\Controllers\OrderController.php:42

Suggested Fix:
  - Add authentication middleware to this route
  - OR guard access using:
    if ($request->user() === null) { ... }

Confidence: 0.92
```

---

## ✨ Features

- 🔍 **Smart Error Interception** - Hooks into Laravel & Symfony exception handling
- 🧠 **AI-Powered Analysis** - Optional AI reasoning for complex errors
- 📚 **Rule-Based Patterns** - Fast, deterministic matching for common errors
- 🎨 **Multiple Output Formats** - Console, logs, or debug UI
- 🔌 **Framework Agnostic Core** - Shared logic between Laravel & Symfony
- 🛡️ **Privacy First** - Sanitized request data, environment-aware
- ⚡ **Non-Blocking** - Never interferes with your application's flow
- 💾 **Explanation Caching** - Cache repeated errors to reduce API calls

---

## 🎯 Supported Error Types

Runtime Insight includes built-in strategies for common PHP errors:

| Error Type | Example | Confidence |
|------------|---------|------------|
| **Null Pointer** | `Call to member function on null` | 0.85 |
| **Undefined Index** | `Undefined array key "user_id"` | 0.88 |
| **Type Error** | `Argument #1 must be of type string, int given` | 0.90 |
| **Argument Count** | `Too few arguments to function` | 0.92 |
| **Class Not Found** | `Class 'App\Models\User' not found` | 0.88 |

Each strategy provides:
- **Cause explanation** - Why the error occurred
- **Suggestions** - Actionable fixes
- **Context-aware hints** - Based on your source code

---

## 📋 Requirements

- **PHP 8.2+**
- **Laravel 10+** or **Symfony 6.4+** (7.x also supported)

---

## 📦 Installation

```bash
composer require clarityphp/runtime-insight
```

## 🚀 Quick Start (Standalone)

```php
use ClarityPHP\RuntimeInsight\RuntimeInsightFactory;

// Create an instance with default configuration
$insight = RuntimeInsightFactory::create();

try {
    // Your code that might throw an exception
    $user->getName(); // Throws: Call to member function on null
} catch (Throwable $e) {
    $explanation = $insight->analyze($e);
    
    echo $explanation->getMessage();     // The error message
    echo $explanation->getCause();       // Why it happened
    echo $explanation->getConfidence();  // 0.85
    
    foreach ($explanation->getSuggestions() as $suggestion) {
        echo "- $suggestion\n";
    }
}
```

### Laravel

The package auto-registers via Laravel's package discovery. Publish the config:

```bash
php artisan vendor:publish --tag=runtime-insight-config
```

**Automatic Exception Analysis:**

Add the trait to your exception handler:

```php
// app/Exceptions/Handler.php
use ClarityPHP\RuntimeInsight\Laravel\Traits\HandlesRuntimeInsight;

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

**Artisan Commands:**

```bash
# Explain the last error
php artisan runtime:explain

# Validate setup
php artisan runtime:doctor
```

### Symfony

Register the bundle in `config/bundles.php`:

```php
return [
    // ...
    ClarityPHP\RuntimeInsight\Symfony\RuntimeInsightBundle::class => ['all' => true],
];
```

Create configuration in `config/packages/runtime_insight.yaml`:

```yaml
runtime_insight:
    enabled: true
    ai:
        enabled: true
        provider: openai
        model: gpt-4.1-mini
```

---

## ⚙️ Configuration

```php
// config/runtime-insight.php (Laravel)
return [
    'enabled' => true,

    'ai' => [
        'enabled' => true,
        'provider' => 'openai',      // openai, anthropic, ollama
        'model' => 'gpt-4.1-mini',
        'timeout' => 5,
    ],

    'context' => [
        'source_lines' => 10,        // Lines of code around error
        'include_request' => true,   // Include request context
        'sanitize_inputs' => true,   // Scrub sensitive data
    ],

    'cache' => [
        'enabled' => true,           // Cache repeated error explanations
        'ttl' => 3600,               // Seconds (default: 1 hour)
    ],

    'environments' => ['local', 'staging'],  // Where to enable
];
```

---

## 🚀 Usage

### Automatic Mode

Once installed, Runtime Insight automatically intercepts exceptions and logs explanations.

### Artisan Commands (Laravel)

```bash
# Explain the most recent runtime error
php artisan runtime:explain

# Explain a specific log entry
php artisan runtime:explain --log=storage/logs/laravel.log --line=243

# Run diagnostics
php artisan runtime:doctor
```

### Console Commands (Symfony)

```bash
# Explain the last exception
php bin/console runtime:explain

# Validate setup
php bin/console runtime:doctor
```

See [USAGE.md](USAGE.md) for detailed documentation.

---

## 🏗️ Architecture

```
┌─────────────────────────┐
│ Framework Adapter       │  ← Laravel / Symfony integration
│ (Laravel / Symfony)     │
└──────────┬──────────────┘
           │
┌──────────▼──────────────┐
│ Runtime Capture Layer   │  ← Exception & Error interception
└──────────┬──────────────┘
           │
┌──────────▼──────────────┐
│ Context Builder         │  ← Source code, request, route info
└──────────┬──────────────┘
           │
┌──────────▼──────────────┐
│ Explanation Engine      │  ← Rule-based + AI reasoning
└──────────┬──────────────┘
           │
┌──────────▼──────────────┐
│ Output Renderer         │  ← Console, Log, Debug UI
└─────────────────────────┘
```

---

## 🔌 Extensibility

Runtime Insight is designed for extensibility:

- **AI Provider Factory** - `ProviderFactory` creates the configured provider (openai, anthropic, ollama) with optional fallback chain
- **Custom AI Providers** - Implement the `AIProviderInterface`
- **Explanation Caching** - When `cache.enabled` is true, the engine caches explanations by error signature (class, message, file, line) to avoid repeated AI calls
- **Custom Explanation Strategies** - Add domain-specific patterns
- **Custom Renderers** - Output to JSON, HTML, Slack, etc.

```php
use ClarityPHP\RuntimeInsight\Contracts\AIProviderInterface;

class CustomAIProvider implements AIProviderInterface
{
    public function getName(): string
    {
        return 'custom';
    }

    public function analyze(RuntimeContext $context): Explanation
    {
        // Your custom AI integration
    }

    public function isAvailable(): bool
    {
        return true;
    }
}
```

---

## 🚫 What This Package Does NOT Do

| ❌ Does NOT | ✅ Does |
|-------------|---------|
| Track errors (like Sentry) | Explain errors |
| Modify your code | Suggest fixes |
| Block requests | Run non-blocking |
| Exfiltrate data | Keep data local |
| Replace error trackers | Complement them |

---

## 🤝 Contributing

Contributions are welcome! Please read our [Contributing Guide](CONTRIBUTING.md) before submitting a Pull Request.

```bash
# Clone the repository
git clone https://github.com/clarityphp/runtime-insight.git
cd runtime-insight

# Install dependencies
composer install

# Run tests
composer test

# Run static analysis
composer analyse

# Check code style
composer cs-check
```

---

## 📄 License

Runtime Insight is open-sourced software licensed under the [MIT license](LICENSE).


