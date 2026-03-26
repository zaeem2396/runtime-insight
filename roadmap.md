# Runtime Insight Roadmap

> ⚠️ **INTERNAL DOCUMENT** - This file is gitignored and not included in releases.

---

## Vision

Runtime Insight is evolving from a simple error explanation tool into a **full runtime intelligence engine** for PHP frameworks—competing conceptually with tools like Laravel Telescope, Sentry, and Datadog, but with **AI-driven debugging and root cause analysis** at its core.

---

## Runtime Intelligence Pipeline

The core architecture is modeled as a **Runtime Intelligence Pipeline**. Events flow through the following stages:

```
Exception
    ↓
Context Builders
    ↓
Signal Collectors
    ↓
Root Cause Analyzer
    ↓
Pattern Analyzer
    ↓
AI Explanation Engine
    ↓
Renderers
```

| Stage | Purpose |
|-------|--------|
| **Exception** | The thrown throwable (or log entry) that triggers the pipeline. |
| **Context Builders** | Build a unified `RuntimeContext` from the exception: stack trace, source snippets, request/route, app env. Framework-specific builders (Laravel, Symfony) add framework context. |
| **Signal Collectors** | Optional collectors that gather runtime signals (queries, requests, queue jobs, cache, memory) to enrich `RuntimeContext` before analysis. |
| **Root Cause Analyzer** | Determines the most likely root cause: analyzes stack traces, correlates request/DB/app context, detects validation gaps, null dereferences, config issues; produces probable fix and prevention advice. |
| **Pattern Analyzer** | Framework- and error-pattern analysis: N+1, inefficient queries, repeated errors, clustering, trends. |
| **AI Explanation Engine** | Rule-based strategies first, then optional AI provider; produces a single `Explanation` (cause, suggestions, confidence). |
| **Renderers** | Output the explanation (console, JSON, Markdown, HTML, IDE). |

---

## Architecture Overview

High-level flow of the runtime intelligence engine:

```
Collectors (Exception, Query, Request, Queue, Cache, Memory)
    ↓
RuntimeContext (enriched)
    ↓
RootCauseAnalyzer
    ↓
PatternAnalyzer (framework-specific + error-pattern)
    ↓
AI Provider (optional)
    ↓
ExplanationEngine
    ↓
Renderers
```

---

## Signal Collectors

**Signal Collectors** gather runtime signals that enrich `RuntimeContext` before analysis. They do not replace Context Builders; they add optional layers of data (queries, recent requests, queue/cache activity, memory) so the Root Cause Analyzer and AI have more to work with.

**Folder concept:**

```
Collectors/
├── ExceptionCollector   # Current exception + chain, already partially in place
├── QueryCollector       # Recent DB queries (e.g. last N before failure)
├── RequestCollector     # Request payload, headers, route (sanitized)
├── QueueCollector       # Queue jobs run/failed around the failure
├── CacheCollector       # Cache hits/misses or keys involved
└── MemoryCollector      # Memory usage over request lifecycle (peak, growth)
```

Collectors are **pluggable** and **configurable** (enable/disable per environment). They run after Context Builders and before Root Cause Analyzer.

---

## Phase 1: Foundation (v0.1.0 - v0.3.0)

### v0.1.0 - Core Architecture ✅ COMPLETED
- [x] Project structure and autoloading
- [x] Core DTOs (RuntimeContext, Explanation, ExceptionInfo, etc.)
- [x] Base interfaces and contracts
- [x] Rule-based explanation engine
- [x] Common PHP error patterns (5 strategies)
- [x] Unit test infrastructure (65 tests)

**Completed Components:**
- `RuntimeInsight` - Main entry point
- `RuntimeInsightFactory` - Easy instantiation
- `Config` - Configuration management
- `ContextBuilder` - Builds RuntimeContext from Throwable
- `ExplanationEngine` - Priority-based strategy chain
- **Strategies:**
  - `NullPointerStrategy` - Null reference errors
  - `UndefinedIndexStrategy` - Array key errors
  - `TypeErrorStrategy` - Type mismatch errors
  - `ArgumentCountStrategy` - Argument count errors
  - `ClassNotFoundStrategy` - Class/interface/trait not found

### v0.2.0 - Laravel Integration ✅ COMPLETED
- [x] Service Provider (fully implemented)
- [x] Exception handler hooks
- [x] Artisan commands (runtime:explain, runtime:doctor)
- [x] Configuration publishing
- [x] Facade implementation
- [x] Laravel-specific context collection

**Completed Components:**
- `LaravelContextBuilder` - Captures request, route, and application context
- `ExceptionHandler` - Automatic exception analysis and logging
- `HandlesRuntimeInsight` trait - Easy exception handler integration
- `ExplainCommand` - Artisan command for explaining errors
- `DoctorCommand` - Artisan command for diagnostics
- `RuntimeInsight` Facade - Clean API for Laravel apps
- Input sanitization with configurable redact fields
- 87 tests, 223 assertions

### v0.3.0 - Symfony Integration ✅ COMPLETED
- [x] Bundle implementation
- [x] Event subscriber for kernel exceptions
- [x] Console commands (runtime:explain, runtime:doctor)
- [x] YAML configuration support
- [x] Symfony-specific context collection

**Completed Components:**
- `RuntimeInsightBundle` - Symfony bundle with dependency injection
- `RuntimeInsightExtension` - DI extension for configuration
- `SymfonyContextBuilder` - Captures request, route, and application context
- `ExceptionSubscriber` - Automatic exception analysis via KernelEvents::EXCEPTION
- `ExplainCommand` - Console command for explaining errors
- `DoctorCommand` - Console command for diagnostics
- Input sanitization with configurable redact fields
- Optional Security component support
- 20 tests, 57 assertions

---

## Phase 2: AI Integration (v0.4.0 - v0.6.0)

### v0.4.0 - OpenAI Provider ✅ COMPLETED
- [x] OpenAI API client
- [x] Prompt engineering for error analysis
- [x] Response parsing and normalization
- [x] Rate limiting and retry logic
- [x] Token usage tracking

**Completed Components:**
- `OpenAIProvider` - Full OpenAI API integration
- Retry logic with exponential backoff for rate limits
- JSON and text response parsing
- Token usage tracking in metadata
- Integration with ExplanationEngine
- Factory method for provider creation
- Laravel and Symfony service provider integration
- 8 tests, 17 assertions

### v0.5.0 - Multi-Provider Support ✅ COMPLETED
- [x] Anthropic Claude integration (`AnthropicProvider`, Messages API, retry, token tracking)
- [x] Ollama integration (`OllamaProvider`, /api/chat, configurable base_url, no API key)
- [x] Provider interface abstraction
- [x] Provider factory (`ProviderFactory`, used by `RuntimeInsightFactory`)
- [x] Fallback chain support (`FallbackChainProvider`, `ai.fallback` config, `Config::withProvider()`)

### v0.6.0 - Advanced Analysis
- [x] Stack trace analysis (StackTraceInfo::getCallChainSummary, RuntimeContext::toSummary includes call chain)
- [ ] Code flow understanding
- [x] Database query context (DatabaseContext, Laravel query log, toSummary)
- [x] Memory and performance context (PerformanceContext, peak memory, toSummary)
- [x] Caching for repeated errors

### Root Cause Analyzer (Phase 2)

**New core component: `RootCauseAnalyzer`**

Sits in the pipeline after Context Builders and Signal Collectors, before the AI Explanation Engine. Responsibilities:

- Determine the **most likely root cause** of runtime failures
- Analyze stack traces (call chain, vendor vs app frames)
- Correlate request, database, and application context
- Detect **missing validation** (e.g. required input absent)
- Detect **null dereferences** and unsafe access patterns
- Detect **configuration issues** (missing env, wrong config keys)
- Generate **probable fix suggestions** (concrete steps)
- Generate **prevention advice** (guards, validation, defaults)

**Example output (conceptual):**

```
Root Cause Analysis
-------------------
Primary cause:    Null dereference — $user was null when calling ->id()
Contributing:     No authentication check on this route; $request->user() can be null.
Context:          GET /orders/recent — no auth middleware. Query log shows 3 queries before failure.

Fix suggestions:
  1. Add auth middleware to this route or controller.
  2. Guard: if ($request->user() === null) { return redirect()->route('login'); }
  3. Use optional: $request->user()?->id() or provide a default.

Prevention:
  - Require authentication for any route that uses $request->user().
  - Consider type-hinting User in controller and using middleware to ensure user is present.
```

---

## Phase 3: Framework Intelligence (v0.6.5 - v0.6.9)

Framework-specific pattern detection to turn runtime signals into actionable insights.

### Laravel Pattern Analyzer

**Component: `LaravelPatternAnalyzer`**

Runs after Root Cause Analyzer when the app is Laravel. Capabilities:

- **N+1 query detection** — Identify N+1 from query log and stack
- **Inefficient Eloquent queries** — Missing select(), large lazy loads
- **Missing eager loading** — Relationships loaded in loops
- **Queue retry failures** — Correlate failed jobs with exceptions
- **Validation issues** — Missing or incorrect validation rules
- **Migration / foreign key errors** — Schema vs runtime mismatch hints
- **Middleware misconfiguration** — Order or missing middleware
- **Rate limiting issues** — Throttle hits and suggested limits

**Example outputs:**

```
[Laravel Pattern] N+1 detected
  Location:    App\Http\Controllers\OrderController::index (line 24)
  Trigger:     Loop over Order with ->user accessed 50 times; 50 extra queries.
  Suggestion:  Eager load: Order::with('user')->get() or $orders->load('user').
```

```
[Laravel Pattern] Validation
  Request:     POST /api/orders
  Missing:     'amount' is required but not validated in StoreOrderRequest.
  Suggestion:  Add rule: 'amount' => 'required|numeric|min:0'.
```

---

## Phase 4: Error Pattern Detection (v0.7.0 - v0.7.5)

System to detect **repeated and related errors** across time and requests.

### Features

- **Error similarity detection** — Same or similar message/stack across events
- **Stack trace clustering** — Group failures by stack shape
- **Incident grouping** — One logical “incident” with multiple occurrences
- **Frequency tracking** — Count by error type, route, or signature
- **Trend detection** — Rising/falling error rates, new patterns

**Example output (grouped errors):**

```
Incident Group: TypeError in OrderController::total (last 24h)
  Signature:   TypeError|Argument #1 must be of type string, null given|OrderController.php:42
  Count:       47 occurrences
  First seen:   2026-02-01 08:12
  Last seen:    2026-02-01 14:33
  Routes:       GET /orders (32), GET /dashboard (15)
  Suggested fix: Add null check or default for argument at OrderController.php:42 (see runtime:explain --log).
```

---

## Phase 5: Developer Experience (v0.8.0 - v0.9.0)

### v0.8.0 - Output & Rendering
- [x] Console output formatter (ConsoleOutputRenderer)
- [x] JSON export (JsonRenderer)
- [x] Markdown export (MarkdownRenderer)
- [x] HTML debug view (HtmlRenderer)
- [x] IDE integration hooks (IdeRenderer, format=ide)

### v0.8.5 - Advanced Commands
- [ ] Batch analysis (analyze all errors in log)
- [ ] Interactive mode
- [ ] Error pattern detection
- [ ] Trend analysis
- [ ] Export to various formats

### v0.9.0 - Customization
- [ ] Custom explanation strategies
- [ ] Plugin system
- [ ] Custom renderers
- [x] Webhook support (HTTP POST after analysis, configurable URLs and headers)
- [x] Event system for extensibility (`BeforeAnalysisEvent`, `AfterAnalysisEvent`, `EventDispatcherInterface`)

### Runtime Timeline Debugging

**New Artisan command: `php artisan runtime:timeline`**

Reconstructs runtime events from logs and context signals to show **what happened before a failure** (requests, queries, queue jobs, cache, etc.).

**Example output:**

```
Runtime Timeline (last failure)
-------------------------------
T+0.00s   Request started    GET /orders/recent
T+0.02s   Query              SELECT * FROM users WHERE id = 1
T+0.05s   Query              SELECT * FROM orders WHERE user_id = 1 ORDER BY created_at DESC
T+0.12s   Exception          TypeError in OrderController::total — Argument #1 null given
T+0.12s   Request ended       HTTP 500
```

### Log Analysis Command

**New command: `php artisan runtime:analyze logs/laravel.log`**

- Scan log files for errors and exceptions
- Summarize error types and counts
- Detect repeated issues (same message/signature)
- Highlight most common failures

**Example output:**

```
Runtime Insight — Log Analysis: logs/laravel.log
==============================================
Period:     Last 7 days (from log file)
Total:      312 errors

Top failures:
  1. TypeError (Argument #1 must be of type string, null given)     — 89
  2. Undefined array key "id"                                       — 54
  3. Class "App\Services\PaymentGateway" not found                  — 23
  4. Too many arguments to function Order::create()                  — 18

Repeated (same signature):
  - OrderController::total TypeError — 47 occurrences (see: runtime:explain --log --line=...)
  - UserController::show Undefined index — 31 occurrences

Run: php artisan runtime:explain --log=logs/laravel.log --line=<line> for details.
```

---

## Phase 6: Production Ready (v1.0.0)

### v1.0.0 - Stable Release
- [ ] Complete documentation
- [ ] Performance optimization
- [ ] Security audit
- [ ] Production environment handling
- [ ] Comprehensive test coverage (>80%)
- [ ] API stability guarantee

---

## AI: Fix Suggestion Generation

Enhance the AI system so it does not only **explain** errors but also **proposes concrete fixes**.

**Capabilities:**

- **Code snippets** — Suggested patch or code block (e.g. null check, validation)
- **Configuration fixes** — Env vars, config keys, feature flags
- **Validation rules** — Laravel/Symfony validation or input checks
- **Architectural improvements** — When to use DTOs, guards, or different design

**Example (suggested fix snippet):**

```
Suggested fix (AI-generated):
  File: app/Http/Controllers/OrderController.php
  Location: around line 42

  // Before (failing):
  return $this->orderService->total($order->user->id);

  // After (suggested):
  $user = $order->user;
  if ($user === null) {
      throw new \InvalidArgumentException('Order has no associated user.');
  }
  return $this->orderService->total($user->id);

  Or use nullsafe: return $this->orderService->total($order->user?->id ?? 0);
```

---

## Incident Analysis Engine

**Capabilities:**

- Summarize **last 24 hours** of runtime events (errors, frequency, routes)
- Detect **spikes** in errors (rate increase, new signatures)
- Identify **root causes** (via Root Cause Analyzer + pattern data)
- Suggest **mitigation steps** (rollback, feature flag, config change)

**Example output:**

```
Incident Summary (last 24h)
--------------------------
Total errors:     312
Unique signatures: 12
Peak hour:        14:00–15:00 (89 errors)

Spike detected:
  TypeError in OrderController::total — 47 in last 6h (was 0 before).
  Likely cause: Recent deploy or data change; argument passed as null.
  Mitigation:  Add validation or default in OrderController::total; consider feature flag to disable new path.

Top routes: GET /orders (120), GET /dashboard (95), POST /api/orders (42).
```

---

## DevOps Integration Phase

- **GitHub Action for runtime analysis** — Workflow step that runs Runtime Insight on failure logs or artifacts
- **CI failure explanation** — Parse CI stack traces (e.g. PHPUnit, PHP) and produce explanations
- **Automatic debugging reports for failed builds** — Attach a short “Runtime Insight” report to the run (e.g. as job summary or artifact)

**Example:**

```yaml
# runtime-insight-action analyzes stack traces from CI failures
- name: Explain PHP failures
  uses: zaeem2396/runtime-insight-action@v1
  if: failure()
  with:
    log-path: 'storage/logs/laravel.log'
    output: 'runtime-insight-report.md'
```

---

## Production Runtime Intelligence Phase

- **Async analysis via queues** — Offload explanation and root-cause analysis to a queue so the request is not blocked
- **Redis caching** — Cache explanations and pattern results by signature (with TTL)
- **Horizontal scaling support** — Stateless analyzers; cache and queue back ends shared across workers
- **Telemetry aggregation** — Optional export of counts/signatures to Prometheus, StatsD, or other metrics for dashboards and alerting

---

## SaaS Expansion (Optional Future)

Potential **hosted platform** offering:

- **Team error dashboards** — Shared view of errors, groups, and trends
- **Runtime analytics** — Error rates, latency impact, deployment correlation
- **Error knowledge base** — Searchable history of incidents and resolutions
- **Collaboration features** — Comments, assignees, links to commits/PRs

This would build on the open-source runtime intelligence pipeline and optional telemetry, without requiring core analysis to move to the cloud.

---

## Future Ideas (Post v1.0)

### Performance & Scaling
- [ ] Queue-based async analysis
- [ ] Redis caching support
- [ ] Horizontal scaling support
- [ ] Metrics and monitoring

### Additional Frameworks
- [ ] CodeIgniter 4 support
- [ ] Slim Framework support
- [ ] WordPress integration
- [ ] Drupal integration

### Advanced Features
- [ ] Real-time error streaming
- [ ] Pattern learning (ML-based)
- [ ] Auto-fix suggestions
- [ ] Test case generation
- [ ] Documentation generation

### SaaS Layer (Optional)
- [ ] Cloud-hosted analysis
- [ ] Team collaboration
- [ ] Error knowledge base
- [ ] Analytics dashboard
- [ ] Billing integration

---

## Technical Debt & Maintenance

### Ongoing
- [ ] PHP version compatibility testing
- [ ] Framework version updates
- [ ] Security updates
- [ ] Dependency updates
- [ ] Performance benchmarking

### Quality
- [ ] Mutation testing
- [ ] Integration tests
- [ ] E2E tests
- [ ] Documentation updates
- [ ] Example maintenance

---

## Priority Matrix

| Feature | Impact | Effort | Priority |
|---------|--------|--------|----------|
| Laravel integration | High | Medium | P0 |
| OpenAI provider | High | Medium | P0 |
| Symfony integration | Medium | Medium | P1 |
| Multi-provider | Medium | Low | P1 |
| Local Ollama | Medium | Low | P2 |
| Root Cause Analyzer | High | Medium | P1 |
| Signal Collectors | Medium | Medium | P2 |
| Framework Intelligence | High | Medium | P2 |
| Error Pattern Detection | Medium | Medium | P2 |
| Advanced commands | Low | Medium | P2 |
| SaaS layer | High | High | P3 |

---

## Release Schedule (Tentative)

| Version | Target Date | Status |
|---------|-------------|--------|
| v0.1.0 | Q1 2026 | ✅ Completed |
| v0.2.0 | Q1 2026 | ✅ Completed |
| v0.3.0 | Q2 2026 | ✅ Completed |
| v0.4.0 | Q2 2026 | ✅ Completed |
| v0.5.0 | Q2 2026 | ✅ Completed |
| v0.6.0 | Q3 2026 | In Progress |
| v0.6.5–0.6.9 | Q3 2026 | Planned (Framework Intelligence) |
| v0.7.0–v0.7.5 | Q3 2026 | Planned (Error Pattern Detection) |
| v0.8.0 | Q3 2026 | Planned (Output & Rendering) |
| v0.8.5 | Q4 2026 | Planned (Advanced Commands) |
| v0.9.0 | Q4 2026 | Planned (Customization) |
| v1.0.0 | Q4 2026 | Planned |

---

## Notes

- Focus on Laravel first due to larger market
- Keep core framework-agnostic for reusability
- Prioritize DX (Developer Experience)
- Security and privacy are non-negotiable
- Performance should never block the main request
- Pipeline stages (Context → Collectors → RootCause → Pattern → AI → Renderers) should remain clear and testable

---

## Changelog

### v0.1.0 (2026-01-21)
- Initial core architecture
- 5 error pattern strategies
- 65 unit tests
- Laravel ServiceProvider (basic)
- RuntimeInsightFactory for standalone usage

### v0.2.0
- Laravel integration: ServiceProvider, exception handler, Artisan commands, Facade
- LaravelContextBuilder, configuration publishing, input sanitization

### v0.3.0
- Symfony integration: Bundle, event subscriber, console commands
- SymfonyContextBuilder, YAML config, optional Security component

### v0.4.0
- OpenAI provider: API client, retry/rate limit handling, token tracking
- ProviderFactory, Laravel/Symfony service integration

### v0.5.0
- Multi-provider: AnthropicProvider, OllamaProvider, ProviderFactory
- FallbackChainProvider, ai.fallback config, Config::withProvider()

### v0.6.0 (in progress)
- Stack trace analysis: getCallChainSummary, call chain in toSummary
- Database query context: DatabaseContext, Laravel query log
- Memory and performance context: PerformanceContext, peak memory
- Caching for repeated errors: ExplanationCacheInterface, CachingExplanationEngine

### v0.9.0 (customization — partial)
- Event hooks: `EventDispatcherInterface`, `InMemoryEventDispatcher`, before/after analysis events
- Optional webhooks: JSON POST to configured URLs after analysis (Guzzle, non-blocking failures)
- Symfony DI: `InMemoryEventDispatcherFactory`, bundle config `webhooks.*`, corrected `services.yaml` path and full `RuntimeInsight` wiring (collectors + analyzers)
