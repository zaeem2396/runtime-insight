# Changelog

All notable changes to this project are documented in this file.

## [Unreleased]

### Added
- Optional HTTP webhooks after each analysis (`AfterAnalysisEvent`).
- `WebhookSenderInterface` and `GuzzleWebhookSender`.
- `WebhookSettings` and `webhooks` configuration (Laravel and Symfony).
- `InMemoryEventDispatcherFactory` for optional webhook listener registration.
