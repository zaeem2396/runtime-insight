# Changelog

All notable changes to this project are documented in this file.

## [Unreleased]

### Added
- `RootCauseResult` includes optional `diagnostics` for automation (e.g. `remediation_category`, frame counts).
- Optional HTTP webhooks after each analysis (`AfterAnalysisEvent`).
- `WebhookSenderInterface` and `GuzzleWebhookSender`.
- `WebhookSettings` and `webhooks` configuration (Laravel and Symfony).
- `InMemoryEventDispatcherFactory` for optional webhook listener registration.
- `EventDispatcherInterface::addListener()` for custom hooks.
- Symfony: full `RuntimeInsight` wiring (collectors, analyzers, dispatcher factory).
- Symfony: correct path to `config/services.yaml` from bundle root.

### Documentation
- README: documentation map table, detailed analysis pipeline and architecture diagram, key type reference, Laravel `AppServiceProvider` listener example, expanded extensibility notes for events and webhooks.
- USAGE: intro links to README/CHANGELOG; full **Events and event dispatcher** section (timing table, contracts, Laravel/Symfony/standalone notes); expanded **Webhooks** reference (enablement, HTTP semantics, config tables, payload example, security); cross-links from Custom Integrations.
- CONTRIBUTING: documentation expectations (README vs USAGE vs CHANGELOG vs Laravel config), PR checklist for config/events/webhooks, pointers to relevant test files.
