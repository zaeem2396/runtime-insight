# Changelog

All notable changes to this project are documented in this file.

## [Unreleased]

### Added
- Optional HTTP webhooks after each analysis (`AfterAnalysisEvent`).
- `WebhookSenderInterface` and `GuzzleWebhookSender`.
- `WebhookSettings` and `webhooks` configuration (Laravel and Symfony).
- `InMemoryEventDispatcherFactory` for optional webhook listener registration.
- `EventDispatcherInterface::addListener()` for custom hooks.
- Symfony: full `RuntimeInsight` wiring (collectors, analyzers, dispatcher factory).
- Symfony: correct path to `config/services.yaml` from bundle root.

### Documentation
- README, USAGE, CONTRIBUTING, and roadmap updated for events and webhooks.
