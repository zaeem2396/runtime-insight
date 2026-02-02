# Runtime Insight Roadmap

> ⚠️ **INTERNAL DOCUMENT** - This file is gitignored and not included in releases.

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

---

## Phase 3: Developer Experience (v0.7.0 - v0.9.0)

### v0.7.0 - Output & Rendering
- [x] Console output formatter (ConsoleOutputRenderer)
- [x] JSON export (JsonRenderer)
- [x] Markdown export (MarkdownRenderer)
- [x] HTML debug view (HtmlRenderer)
- [x] IDE integration hooks (IdeRenderer, format=ide)

### v0.8.0 - Advanced Commands
- [ ] Batch analysis (analyze all errors in log)
- [ ] Interactive mode
- [ ] Error pattern detection
- [ ] Trend analysis
- [ ] Export to various formats

### v0.9.0 - Customization
- [ ] Custom explanation strategies
- [ ] Plugin system
- [ ] Custom renderers
- [ ] Webhook support
- [ ] Event system for extensibility

---

## Phase 4: Production Ready (v1.0.0)

### v1.0.0 - Stable Release
- [ ] Complete documentation
- [ ] Performance optimization
- [ ] Security audit
- [ ] Production environment handling
- [ ] Comprehensive test coverage (>80%)
- [ ] API stability guarantee

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
| v0.7.0 | Q3 2026 | Planned |
| v0.8.0 | Q3 2026 | Planned |
| v0.9.0 | Q4 2026 | Planned |
| v1.0.0 | Q4 2026 | Planned |

---

## Notes

- Focus on Laravel first due to larger market
- Keep core framework-agnostic for reusability
- Prioritize DX (Developer Experience)
- Security and privacy are non-negotiable
- Performance should never block the main request

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
