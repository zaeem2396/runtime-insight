# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

Nothing yet.

## [0.1.0] - 2026-04-07

### Added

- **Root cause analysis (Phase 2):** `RootCauseAnalyzer` composes `Engine\RootCause\PrimaryCauseInferencer`, `StackTraceAnalyzer`, `ContributingNarrator`, `ContextSummaryBuilder`, and `RemediationBuilder`. `RootCauseResult` includes optional **`diagnostics`** (e.g. `remediation_category`, vendor vs application frame counts, first application frame) for automation and richer primary cause, contributing text, context summary, fixes, and prevention. Broader inference for SQL (PDO and constraints), validation, configuration, parse errors, argument count, not found, division by zero, and related cases.
- **Events:** synchronous `BeforeAnalysisEvent` and `AfterAnalysisEvent`; `EventDispatcherInterface::addListener()` for custom hooks; `InMemoryEventDispatcher` and `InMemoryEventDispatcherFactory` (Laravel, Symfony, and standalone factory wiring).
- **Webhooks:** optional HTTP POST after each analysis via `AfterAnalysisWebhookListener` on `AfterAnalysisEvent`; `WebhookSenderInterface`, `GuzzleWebhookSender`, `WebhookSettings`, and `webhooks` configuration (Laravel and Symfony).
- **Symfony:** full `RuntimeInsight` service wiring (collectors, analyzers, dispatcher factory); corrected path to `config/services.yaml` from the bundle root.

### Changed

- **`EventDispatcherInterface`** now includes `addListener(string $eventClass, callable $listener): void`. Any third-party implementation of this contract must implement this method (package default implementations are updated).

### Documentation

- **README:** documentation map; analysis pipeline and architecture overview; key types table (including root cause); cross-links to USAGE (events, root cause analysis, webhooks); Laravel `AppServiceProvider` listener example; extensibility notes for events and webhooks.
- **USAGE:** intro links to README/CHANGELOG; **Root cause analysis** (signals table, `root_cause` JSON shape including `diagnostics`, custom analyzer); **Events and event dispatcher** (timing, contracts, Laravel/Symfony/standalone); **Webhooks** (enablement, HTTP semantics, config, payload example, security); cross-links from Custom Integrations.
- **CONTRIBUTING:** documentation expectations (README vs USAGE vs CHANGELOG vs Laravel config); PR checklist for config, events, and webhooks; pointers to tests including `RootCauseAnalyzerTest` and `tests/Unit/Engine/RootCause/`.

[Unreleased]: https://github.com/zaeem2396/runtime-insight/compare/v0.9.0...HEAD
[0.9.0]: https://github.com/zaeem2396/runtime-insight/releases/tag/v0.9.0
