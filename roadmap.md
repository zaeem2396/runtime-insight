# Runtime Insight Roadmap

> ⚠️ **INTERNAL DOCUMENT** - This file is gitignored and not included in releases.

---

## Roadmap status labels

| Label | Meaning |
|-------|---------|
| **Completed** | Shipped in the codebase and maintained in the current release line. |
| **In progress** | Partially shipped or under active iteration; broader scope still planned. |
| **Planned** | Agreed direction; not yet implemented. |

---

## Phases at a glance

| Phase | Versions | Overall status | Focus |
|-------|----------|----------------|-------|
| Phase 1 | v0.1.0–v0.3.0 | Completed | Core engine, Laravel, Symfony |
| Phase 2 | v0.4.0–v0.6.0 | In progress | AI providers, advanced context, root cause |
| Phase 3 | v0.6.5–v0.6.9 | In progress | Framework intelligence (`LaravelPatternAnalyzer`, collectors) |
| Phase 4 | v0.7.0–v0.7.5 | Planned | Cross-request patterns, incidents, trends |
| Phase 5 | v0.8.0–v0.9.0 | In progress | Renderers, log commands, events, webhooks |
| Phase 6 | v1.0.0 | Planned | Stable release hardening |

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

| Collector (implementation) | Status | Notes |
|------------------------------|--------|-------|
| `ExceptionCollector` | Completed | Wired via `CollectorRegistry` |
| `QueryCollector` | Completed | Recent DB signals for context |
| `RequestCollector` | Completed | Sanitized request/route payload |
| `QueueCollector` | Completed | Queue-related signals |
| `CacheCollector` | Completed | Cache-related signals |
| `MemoryCollector` | Completed | Peak memory / lifecycle hints |

Collectors are **pluggable** and **configurable** (enable/disable per environment). They run after Context Builders and before Root Cause Analyzer.

---

## Phase 1: Foundation (v0.1.0 - v0.3.0)

### v0.1.0 - Core Architecture

| Deliverable | Status | Notes |
|-------------|--------|-------|
| Project structure and autoloading | Completed | PSR-4, Composer |
| Core DTOs (`RuntimeContext`, `Explanation`, `ExceptionInfo`, …) | Completed | |
| Base interfaces and contracts | Completed | |
| Rule-based explanation engine | Completed | `ExplanationEngine` |
| Common PHP error patterns (5+ strategies) | Completed | Null, index, type, arg count, class not found, … |
| Unit test infrastructure | Completed | Grew beyond initial 65 tests |

| Component | Status | Notes |
|-----------|--------|-------|
| `RuntimeInsight` | Completed | Main entry point |
| `RuntimeInsightFactory` | Completed | Standalone wiring |
| `Config` | Completed | Configuration container |
| `ContextBuilder` | Completed | `RuntimeContext` from `Throwable` |
| `ExplanationEngine` | Completed | Priority-based strategy chain |

### v0.2.0 - Laravel Integration

| Deliverable | Status | Notes |
|-------------|--------|-------|
| Service provider | Completed | Package discovery |
| Exception handler hooks | Completed | Optional trait + handler |
| Artisan commands | Completed | `runtime:explain`, `runtime:doctor`, … |
| Configuration publishing | Completed | `config/runtime-insight.php` |
| Facade | Completed | `RuntimeInsight` |
| Laravel-specific context | Completed | `LaravelContextBuilder` |
| Input sanitization / redact fields | Completed | Configurable |

### v0.3.0 - Symfony Integration

| Deliverable | Status | Notes |
|-------------|--------|-------|
| Bundle & DI extension | Completed | `RuntimeInsightBundle`, `RuntimeInsightExtension` |
| Kernel exception subscriber | Completed | `ExceptionSubscriber` |
| Console commands | Completed | `runtime:explain`, `runtime:doctor`, … |
| YAML configuration | Completed | `runtime_insight.*` |
| Symfony-specific context | Completed | `SymfonyContextBuilder` |
| Optional Security component | Completed | When present |

---

## Phase 2: AI Integration (v0.4.0 - v0.6.0)

### v0.4.0 - OpenAI Provider

| Deliverable | Status | Notes |
|-------------|--------|-------|
| OpenAI API client | Completed | `OpenAIProvider` |
| Prompting & response handling | Completed | JSON/text parsing |
| Rate limiting & retry | Completed | Exponential backoff |
| Token usage tracking | Completed | Metadata on explanations |
| Laravel / Symfony wiring | Completed | Via `RuntimeInsightFactory` |

### v0.5.0 - Multi-Provider Support

| Deliverable | Status | Notes |
|-------------|--------|-------|
| Anthropic Claude | Completed | `AnthropicProvider`, Messages API |
| Ollama | Completed | `OllamaProvider`, configurable `base_url` |
| Provider abstraction | Completed | `AIProviderInterface` |
| Provider factory | Completed | `ProviderFactory` |
| Fallback chain | Completed | `FallbackChainProvider`, `ai.fallback`, `Config::withProvider()` |

### v0.6.0 - Advanced Analysis

| Deliverable | Status | Notes |
|-------------|--------|-------|
| Stack trace / call chain summary | Completed | `StackTraceInfo::getCallChainSummary`, `toSummary` |
| Code flow understanding | Planned | Deeper static / path analysis |
| Database query context | Completed | `DatabaseContext`, Laravel query log |
| Memory & performance context | Completed | `PerformanceContext`, peak memory |
| Caching repeated explanations | Completed | `ExplanationCacheInterface`, `CachingExplanationEngine` |

### Root Cause Analyzer (Phase 2)

**Core component: `RootCauseAnalyzer`** — sits after Context Builders and Signal Collectors, before the AI Explanation Engine.

| Responsibility | Status | Notes |
|----------------|--------|-------|
| Primary cause from exception class/message | Completed | Heuristics for common PHP failures |
| Stack / context summary for AI & output | Completed | `RootCauseResult` |
| Fix suggestions & prevention advice | In progress | Rule-based; richer cases ongoing |
| Deep validation / config gap detection | In progress | Expand beyond message heuristics |
| Vendor vs app frame analysis | In progress | Call chain present; deeper correlation planned |

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

**Component: `LaravelPatternAnalyzer`** — runs after Root Cause Analyzer when the app is Laravel.

| Capability | Status | Notes |
|------------|--------|-------|
| High query-count / N+1-style hints | Completed | Threshold + query log / collector signals |
| Validation-related message hints | Completed | Keyword-based hints on exception message |
| Inefficient Eloquent / `select()` / lazy loads | Planned | Deeper static + log correlation |
| Missing eager loading in loops | Planned | Pair with query log shape |
| Queue retry correlation | Planned | Tie failures to job payloads |
| Migration / foreign key mismatch hints | Planned | |
| Middleware misconfiguration | Planned | |
| Rate limiting / throttle analysis | Planned | |

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

| Feature | Status | Notes |
|---------|--------|-------|
| Error similarity detection | Planned | Across events / time windows |
| Stack trace clustering | Planned | Group by stack shape |
| Incident grouping | Planned | One logical incident, many occurrences |
| Frequency tracking | In progress | `runtime:analyze` summarizes counts & top signatures in a log file |
| Trend detection | Planned | Rising/falling rates, new patterns |

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

| Deliverable | Status | Notes |
|-------------|--------|-------|
| Console output (`ConsoleOutputRenderer`) | Completed | Default CLI output |
| JSON (`JsonRenderer`) | Completed | |
| Markdown (`MarkdownRenderer`) | Completed | |
| HTML (`HtmlRenderer`) | Completed | Debug-style view |
| IDE format (`IdeRenderer`, `format=ide`) | Completed | File:line first line |

### v0.8.5 - Advanced Commands (roadmap checklist)

| Deliverable | Status | Notes |
|-------------|--------|-------|
| Batch analysis (full pass over large logs) | Planned | Distinct from one-file `runtime:analyze` summary |
| Interactive mode | Planned | |
| Deep error pattern detection | Planned | Phase 4–aligned |
| Trend analysis | Planned | Time-series style |
| Export to various formats | In progress | Renderers exist; dedicated export UX TBD |

### Log & timeline commands (shipped under Phase 5)

| Command / capability | Status | Notes |
|----------------------|--------|-------|
| `runtime:timeline` (Laravel & Symfony) | Completed | Events before last failure from log |
| `runtime:analyze` (log summary) | Completed | Totals, top failures, repeated signatures |

### v0.9.0 - Customization

| Deliverable | Status | Notes |
|-------------|--------|-------|
| Custom explanation strategies (`strategies` config) | Planned | Configurable FQCNs implementing `ExplanationStrategyInterface` |
| Plugin system | Planned | |
| Custom renderers (beyond built-ins) | Planned | Implement `RendererInterface` today; first-class plugin TBD |
| Webhook support | Completed | HTTP POST after analysis, configurable URLs/headers |
| Event system | Completed | `BeforeAnalysisEvent`, `AfterAnalysisEvent`, `EventDispatcherInterface` |

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

| Deliverable | Status | Notes |
|-------------|--------|-------|
| Complete documentation | In progress | README, USAGE, CHANGELOG maintained; polish ongoing |
| Performance optimization | Planned | |
| Security audit | Planned | |
| Production environment handling | Planned | Safer defaults, ops guidance |
| Comprehensive test coverage (>80%) | Planned | |
| API stability guarantee | Planned | SemVer commitments |

---

## AI: Fix Suggestion Generation

Enhance the AI system so it does not only **explain** errors but also **proposes concrete fixes**.

| Capability | Status | Notes |
|------------|--------|-------|
| Code snippets (patches, null checks, validation) | Planned | Structured output from providers |
| Configuration fixes (env, keys, flags) | Planned | |
| Validation rules (Laravel / Symfony) | Planned | |
| Architectural guidance (DTOs, guards, design) | Planned | |

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

| Capability | Status | Notes |
|------------|--------|-------|
| Summarize recent runtime events (errors, frequency, routes) | Planned | e.g. 24h windows |
| Spike detection (rate / new signatures) | Planned | |
| Root cause roll-up | Planned | Combine `RootCauseAnalyzer` + pattern store |
| Mitigation suggestions (rollback, flags, config) | Planned | |

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

| Capability | Status | Notes |
|------------|--------|-------|
| GitHub Action for runtime analysis | Planned | Workflow step on logs/artifacts |
| CI failure explanation (PHPUnit/PHP traces) | Planned | |
| Automatic debugging reports on failed builds | Planned | Job summary or artifact |

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

| Capability | Status | Notes |
|------------|--------|-------|
| Async analysis via queues | Planned | Offload work off the request |
| Redis (or shared) cache for explanations / patterns | Planned | Beyond in-process `CachingExplanationEngine` |
| In-request / in-memory explanation cache | Completed | Configurable TTL via `cache.*` |
| Horizontal scaling (stateless workers) | Planned | Shared cache/queue backends |
| Telemetry export (Prometheus, StatsD, …) | Planned | Counts/signatures for dashboards |

---

## SaaS Expansion (Optional Future)

Potential **hosted platform** offering:

| Capability | Status | Notes |
|------------|--------|-------|
| Team error dashboards | Planned | Shared errors, groups, trends |
| Runtime analytics | Planned | Rates, latency, deploy correlation |
| Error knowledge base | Planned | Searchable incidents & fixes |
| Collaboration | Planned | Comments, assignees, PR links |

This would build on the open-source runtime intelligence pipeline and optional telemetry, without requiring core analysis to move to the cloud.

---

## Future Ideas (Post v1.0)

### Performance & Scaling

| Idea | Status |
|------|--------|
| Queue-based async analysis | Planned |
| Redis caching support | Planned |
| Horizontal scaling support | Planned |
| Metrics and monitoring | Planned |

### Additional Frameworks

| Idea | Status |
|------|--------|
| CodeIgniter 4 support | Planned |
| Slim Framework support | Planned |
| WordPress integration | Planned |
| Drupal integration | Planned |

### Advanced Features

| Idea | Status |
|------|--------|
| Real-time error streaming | Planned |
| Pattern learning (ML-based) | Planned |
| Auto-fix suggestions | Planned |
| Test case generation | Planned |
| Documentation generation | Planned |

### SaaS Layer (Optional)

| Idea | Status |
|------|--------|
| Cloud-hosted analysis | Planned |
| Team collaboration | Planned |
| Error knowledge base | Planned |
| Analytics dashboard | Planned |
| Billing integration | Planned |

---

## Technical Debt & Maintenance

### Ongoing

| Item | Status | Notes |
|------|--------|-------|
| PHP version compatibility testing | In progress | CI matrix / local checks |
| Framework version updates | In progress | Laravel & Symfony supported ranges |
| Security updates | In progress | Dependencies & advisories |
| Dependency updates | In progress | Composer ecosystem |
| Performance benchmarking | Planned | Formal baselines |

### Quality

| Item | Status | Notes |
|------|--------|-------|
| Mutation testing | Planned | |
| Integration tests | In progress | PHPUnit + Testbench / Symfony |
| E2E tests | Planned | Full app scenarios |
| Documentation updates | In progress | README, USAGE, CHANGELOG |
| Example maintenance | Planned | Sample apps / snippets |

---

## Priority Matrix

| Feature | Status | Impact | Effort | Priority |
|---------|--------|--------|--------|----------|
| Laravel integration | Completed | High | Medium | P0 |
| OpenAI provider | Completed | High | Medium | P0 |
| Symfony integration | Completed | Medium | Medium | P1 |
| Multi-provider | Completed | Medium | Low | P1 |
| Local Ollama | Completed | Medium | Low | P2 |
| Root Cause Analyzer | In progress | High | Medium | P1 |
| Signal Collectors | Completed | Medium | Medium | P2 |
| Framework Intelligence | In progress | High | Medium | P2 |
| Error Pattern Detection | Planned | Medium | Medium | P2 |
| Advanced commands | In progress | Low | Medium | P2 |
| SaaS layer | Planned | High | High | P3 |

---

## Release Schedule (Tentative)

| Version | Target date | Status | Notes |
|---------|-------------|--------|-------|
| v0.1.0 | Q1 2026 | Completed | Core architecture |
| v0.2.0 | Q1 2026 | Completed | Laravel |
| v0.3.0 | Q2 2026 | Completed | Symfony |
| v0.4.0 | Q2 2026 | Completed | OpenAI provider |
| v0.5.0 | Q2 2026 | Completed | Multi-provider & fallback |
| v0.6.0 | Q3 2026 | In progress | Most items shipped; code-flow depth Planned |
| v0.6.5–v0.6.9 | Q3 2026 | In progress | Framework intelligence (subset Completed) |
| v0.7.0–v0.7.5 | Q3 2026 | Planned | Error pattern / incidents |
| v0.8.0 | Q3 2026 | Completed | Output renderers |
| v0.8.5 | Q4 2026 | Planned | Batch/interactive/trend checklist |
| v0.9.0 | Q4 2026 | In progress | Events & webhooks Completed; plugins/strategies Planned |
| v1.0.0 | Q4 2026 | Planned | Stable release |

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

### v0.6.0

| Area | Status |
|------|--------|
| Stack trace / call chain in summaries | Completed |
| Database query context (Laravel log) | Completed |
| Memory & performance context | Completed |
| Caching for repeated errors | Completed |
| Code flow understanding | Planned |

### v0.9.0 (customization)

| Area | Status |
|------|--------|
| Event hooks (`EventDispatcherInterface`, before/after analysis) | Completed |
| Optional webhooks (JSON POST, configurable URLs) | Completed |
| Symfony DI: dispatcher factory, `webhooks.*`, full `RuntimeInsight` wiring | Completed |
| Custom explanation strategies (`strategies`) | Planned |
| Plugin system & first-class custom renderers | Planned |
