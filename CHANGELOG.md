# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [0.3.0] OpenTelemetry Ingestion - 2026-11-23

### Added
- **OpenTelemetry ingestion** — `$langfuse->otelIngestion()` exports traces, spans and generations to `POST /api/public/otel/v1/traces` (OTLP/HTTP JSON). Langfuse Cloud stopped accepting trace, span and generation events on the v2 ingestion endpoint on 2026-11-16
- Follows the OTel span lifecycle: `trace()` / `span()` / `generation()` start an observation, `update()` changes it, `end()` exports it once
- **Deterministic IDs** — `OtelIngestion::traceId($seed)` and `OtelIngestion::spanId($seed)`, so one trace can span several processes
- **Levels** — `ObservationLevel` enum; `ERROR` is exported as OTel status `ERROR` with the status message
- **Version** — `Langfuse` constructor accepts a `$version` parameter, attached to OTel spans

### Changed
- PHP 8.3 support: removed property hooks from `Ingestion\Trace`, `Ingestion\Span` and `Ingestion\Generation` (`$id` is still readable)
- `ingestion()` is unchanged and still posts to `/api/public/ingestion`

### Fixed
- `postJson()` and `patchJson()` replace invalid UTF-8 instead of sending an empty body

## [0.2.0] Scores, Ingestion Rewrite & Prompt Enhancements - 2026-02-19

### Added
- **Score resource** — full CRUD support (`create`, `get`, `list`, `delete`) via `$langfuse->score()`
- **Score enums** — `ScoreDataType` (`NUMERIC`, `CATEGORICAL`, `BOOLEAN`) and `ScoreSource` (`API`, `ANNOTATION`, `EVAL`)
- **Fluent ingestion API** — `trace()` returns a `Trace` object, `generation()` returns a `Generation` object, both with `update()` methods
- **Span support** — new `Span` class with `update()`, nested `span()` and `generation()` methods
- **Prompt label updates** — new `Prompt::update()` method to patch labels on a specific prompt version
- **Auto-paginating prompt list** — `Prompt::list()` now returns a `Generator` that iterates all pages transparently
- **Default label** — `Langfuse` constructor accepts a `$label` parameter (defaults to `'latest'`), applied when no version or label is provided
- **Transporter methods** — `delete()` and `patchJson()` added to `TransporterInterface` and `HttpTransporter`
- **Score response objects** — `ScoreResponse` and `ScoreListResponse`
- **Static helpers** — `Ingestion::uuid()` and `Ingestion::now()`

### Changed
- `Ingestion::trace()` — `$name` is now the first required parameter; `$traceId` is optional (auto-generated). Returns `Trace` instead of `void`
- `Ingestion::generation()` — signature reordered, `$sessionId` removed. Returns `Generation` instead of `void`
- `Langfuse::ingestion()` — no longer accepts `$environment`; set it via the `Langfuse` constructor
- `Prompt::list()` — returns `Generator<PromptListItem>` instead of `PromptListResponse`; `$page` parameter removed
- `Prompt::text()` / `Prompt::chat()` — `$version` parameter type changed from `?string` to `?int`
- Scores and observations now use v2 API endpoints, aligned with the Langfuse OpenAPI spec

### Fixed
- Handle minimal create response from Langfuse v3 scores API
- PHPStan type improvements for score iterables

## [0.1.4] Ingestion - 2025-08-08

### Added
- `Ingestion` class for handling trace and generation events
- `Langfuse::ingestion()` method to access the ingestion API
- Feature tests for ingestion trace and generation functionality
- Optional `$id` and `$generationId` parameters on ingestion methods

### Changed
- `Prompt` config property now uses `mixed` type hinting
- Improved CONTRIBUTING.md with detailed guidelines for development setup, code quality, testing, and pull requests
- Updated README with ingestion feature documentation and usage examples
- Renamed `Langfuse.php` test to `PromptTest.php`

## [0.1.3] Tests & Refactor - 2025-08-05

### Added
- Unit tests for `ChatPromptResponse` and `TextPromptResponse`
- PHPStan ignore comments for `isActive` property in response tests

### Changed
- Refactored constructor formatting and updated type hints for `isActive` property in response classes
- Renamed `PostPromptReponse` to `PostPromptResponse` (typo fix)
- Renamed `PostChatPromptReponse` to `PostChatPromptResponse` (typo fix)

### Fixed
- Peck errors
- Code style fixes via Pint

## [0.1.2] Stability - 2025-06-30

### Changed
- Set minimum stability to `stable` in composer.json

## [0.1.0] Initial Release - 2025-06-30

### Added
- Prompt fetching (text and chat) via Langfuse v2 API
- Prompt creation with labels, config, and tags
- Prompt listing with pagination
- Template compilation via `IsCompilable` concern
- Fallback prompts when API is unavailable
- `InvalidPromptTypeException` for type mismatches
- `HttpTransporter` with error handling for 401, 403, 404, 405, and 500 responses
- PHP 8.3 support
- PHPStan static analysis
- Peck typo checking
- Full test suite with Pest
