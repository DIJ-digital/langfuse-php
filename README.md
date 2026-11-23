## Langfuse PHP - A PHP Client for Langfuse API

This package provides a wrapper around the [Langfuse](https://langfuse.com) API, allowing you to easily integrate Langfuse into your PHP applications. It uses as few dependencies as possible.

### This package supports the following features:

#### Prompts

- Get text prompts
- Get chat prompts
- Compile text prompts
- Compile chat prompts
- Create text prompts
- Create chat prompts
- List prompts (auto-paginated)
- Update prompt labels
- Fallback handling for prompt fetching errors
- Fallback handling when no prompt is found

#### Ingestion

- Create and update traces
- Create and update spans (with nesting)
- Create and update generations
- Automatic `traceId` and `parentObservationId` threading
- Sends directly to the [Langfuse v2 ingestion API](https://api.reference.langfuse.com/#POST/api/public/ingestion)

#### OpenTelemetry Ingestion

- Full OTLP/HTTP JSON support via Langfuse's OTel endpoint
- Same fluent API as standard ingestion (`trace` / `span` / `generation`)
- Maps all `langfuse.*` namespace attributes (trace, observation, metadata)
- 128-bit hex trace IDs, 64-bit hex span IDs, nanosecond timestamps

#### Scores

- Create scores
- Get scores
- List scores
- Delete scores
- V2 API support for scores

> **Requires [PHP 8.3+](https://php.net/releases/)**

Install the package using **Composer**:

```bash
composer require dij-digital/langfuse-php
```

### How to use this package

#### Setup

```php
use DIJ\Langfuse\PHP\Langfuse;
use DIJ\Langfuse\PHP\Transporters\HttpTransporter;
use GuzzleHttp\Client;

$langfuse = new Langfuse(
    transporter: new HttpTransporter(new Client([
        'base_uri' => 'https://cloud.langfuse.com',
        'auth' => ['PUBLIC_KEY', 'SECRET_KEY'],
    ])),
    environment: 'production', // optional, defaults to 'default'
    version: '1.0.0',         // optional, attached to OTel spans
);
```

#### Prompts

```php
// Get and compile prompts
$langfuse->prompt()->text(promptName: 'promptName')->compile(params: ['key' => 'value']);
$langfuse->prompt()->chat(promptName: 'chatName')->compile(params: ['key' => 'value']);

// List all prompts (returns a Generator that auto-paginates)
foreach ($langfuse->prompt()->list() as $prompt) {
    echo $prompt->name;
}

// Create a prompt
$langfuse->prompt()->create(promptName: 'promptName', prompt: 'text', type: PromptType::TEXT);

// Update prompt labels
$langfuse->prompt()->update(promptName: 'promptName', version: 1, labels: ['production']);
```

#### Ingestion

Every call to `trace()`, `span()`, or `generation()` immediately sends a request to the Langfuse ingestion API. No buffering, no flushing required.

```php
$ingestion = $langfuse->ingestion();
```

##### Trace

A trace is the root of an observation tree.

```php
$trace = $ingestion->trace(
    name: 'my-workflow',
    userId: 'user-456',
    input: 'user question',
);

// Update the trace (sends immediately)
$trace->update(
    output: 'final answer',
    metadata: ['duration_ms' => 1234],
);
```

##### Span

Spans group related work within a trace. Create them from a `Trace` or another `Span` -- `traceId` and `parentObservationId` are set automatically.

```php
// Create a span from the trace
$span = $trace->span(name: 'web-search-batch');

// Nest a child span under the parent span
$childSpan = $span->span(name: 'single-search');

// Update spans when work is done
$childSpan->update(output: ['results' => 3], endTime: date('c'));
$span->update(output: ['total' => 3], endTime: date('c'));
```

##### Generation

Generations represent LLM calls. Create them from a `Trace` or `Span` -- context IDs are threaded automatically.

```php
// Generation on a trace
$gen = $trace->generation(
    input: ['messages' => [['role' => 'user', 'content' => 'Hello']]],
    output: 'Hi there!',
    name: 'llm-call',
    model: 'gpt-4o',
    modelParameters: ['temperature' => 0.7],
    promptName: 'my-prompt',
    promptVersion: 1,
);

// Generation nested under a span
$gen = $span->generation(
    input: 'summarize this',
    output: 'summary text',
    name: 'summarize-call',
    model: 'gpt-4o',
);

// Update a generation after the LLM responds
$gen->update(
    output: 'updated response',
    metadata: ['tokens' => 150],
);
```

##### Full example

```php
$ingestion = $langfuse->ingestion();

$trace = $ingestion->trace(
    name: 'handle-request',
    userId: 'user-789',
    input: 'What is the weather?',
);

$span = $trace->span(name: 'search-batch');

    $child = $span->span(name: 'weather-api-call');
    $child->update(output: ['temp' => 22], endTime: date('c'));

    $span->generation(
        input: 'Summarize weather data',
        output: 'It is 22 degrees and sunny.',
        name: 'summarize',
        model: 'gpt-4o',
    );

$span->update(output: ['answer' => 'It is 22 degrees.'], endTime: date('c'));
$trace->update(output: 'It is 22 degrees and sunny.');
```

#### OpenTelemetry Ingestion

Posts spans to Langfuse's OTLP/HTTP JSON endpoint (`/api/public/otel/v1/traces`). Langfuse Cloud no longer accepts traces, spans or generations on the v2 ingestion endpoint, so use this for tracing. `ingestion()` keeps working unchanged for self-hosted instances that still accept it.

It follows the OpenTelemetry span lifecycle: `trace()`, `span()` and `generation()` start an observation, `update()` changes it locally, and `end()` exports it once. Nothing is sent before `end()`, and an observation that is never ended is never exported.

```php
use DIJ\Langfuse\PHP\Enums\ObservationLevel;

$otel = $langfuse->otelIngestion(serviceName: 'weather-app');

$trace = $otel->trace(
    name: 'handle-request',
    userId: 'user-123',
    sessionId: 'sess-456',
    input: 'What is the weather?',
    tags: ['production', 'weather'],
);

$span = $trace->span(name: 'web-search', input: ['query' => 'weather today']);
$span->update(output: ['results' => 3]);
$span->end();

$generation = $trace->generation(
    name: 'summarize',
    input: 'Summarize the weather data',
    model: 'gpt-4o',
    modelParameters: ['temperature' => 0.7],
    promptName: 'weather-summary',
    promptVersion: 2,
);
$generation->update(output: 'It is 22 degrees and sunny.', usageDetails: ['input' => 120, 'output' => 12]);
$generation->end();

$trace->update(output: 'It is 22 degrees and sunny.');
$trace->end();
```

Mark a failed observation with `update(level: ObservationLevel::ERROR, statusMessage: '...')`; it is exported with OTel status `ERROR`. Otherwise the status stays unset.

Trace-level attributes (name, user, session, tags, release, public, trace metadata), the environment and the version are copied onto every child observation, so you can filter observations by them.

To build one trace across several processes (for example queued jobs), derive the IDs from a stable seed. Exporting the same root span from another process upserts the trace.

```php
$traceId = OtelIngestion::traceId(seed: 'run-42');
$rootSpanId = OtelIngestion::spanId(seed: 'run-42');

$otel->trace(name: 'run', traceId: $traceId, spanId: $rootSpanId, startTime: $runStartedAt)->end();

$step = $otel->span(traceId: $traceId, parentSpanId: $rootSpanId, name: 'step-1', spanId: OtelIngestion::spanId(seed: 'step-1'));
$step->end();
```

Trace IDs must be 32 and span IDs 16 lowercase hex characters; anything else throws an `InvalidArgumentException`. Times are ISO 8601 strings and default to now.

| Langfuse field | OTel attribute |
|---|---|
| Trace name | `langfuse.trace.name` |
| User ID | `langfuse.user.id` |
| Session ID | `langfuse.session.id` |
| Trace input/output | root observation input/output and `langfuse.trace.input` / `langfuse.trace.output` |
| Tags | `langfuse.trace.tags` |
| Public | `langfuse.trace.public` |
| Release | `langfuse.release` |
| Metadata | `langfuse.trace.metadata.<key>` / `langfuse.observation.metadata.<key>` |
| Observation type | `langfuse.observation.type` |
| Observation input/output | `langfuse.observation.input` / `langfuse.observation.output` |
| Level / status message | `langfuse.observation.level` / `langfuse.observation.status_message` |
| Model | `langfuse.observation.model.name` |
| Model parameters | `langfuse.observation.model.parameters` |
| Usage | `langfuse.observation.usage_details` |
| Prompt | `langfuse.observation.prompt.name` / `langfuse.observation.prompt.version` |
| Environment | `langfuse.environment` |
| Version | `langfuse.version` |
| Service | resource attribute `service.name` |

#### Scores

```php
use DIJ\Langfuse\PHP;
use DIJ\Langfuse\PHP\Enums\ScoreDataType;

// Create a score
$score = $langfuse->score()->create(
    traceId: 'trace-id-123',
    name: 'accuracy',
    value: 0.95,
    dataType: ScoreDataType::NUMERIC,
    comment: 'High accuracy score'
);

// Get a specific score (using v2 API)
$score = $langfuse->score()->get('score-id-123');

// List scores with filters (using v2 API)
$scores = $langfuse->score()->list(
    traceId: 'trace-id-123',
    dataType: ScoreDataType::NUMERIC,
    limit: 10
);

// Delete a score
$langfuse->score()->delete('score-id-123');
```

### Architecture

```
Langfuse(transporter, environment?, version?)
├── prompt()                → Prompt
│                                 ├── text()     → TextPromptResponse|FallbackPrompt
│                                 ├── chat()     → ChatPromptResponse|FallbackPrompt
│                                 ├── list()     → Generator<PromptListItem>
│                                 ├── create()   → TextPromptResponse|ChatPromptResponse
│                                 └── update()   → TextPromptResponse|ChatPromptResponse
├── ingestion()             → Ingestion
│                             ├── trace()      → Trace
│                             │                   ├── update()
│                             │                   ├── span()       → Span
│                             │                   └── generation() → Generation
│                             ├── span()       → Span
│                             │                   ├── update()
│                             │                   ├── span()       → Span
│                             │                   └── generation() → Generation
│                             └── generation() → Generation
│                                                 └── update()
├── otelIngestion()         → OtelIngestion
│                             ├── trace()      → OtelIngestion\Trace
│                             ├── span()       → OtelIngestion\Span
│                             └── generation() → OtelIngestion\Generation
│                                                 each has update(), span(), generation() and end()
└── score()                 → Score
                              ├── create()
                              ├── get()
                              ├── list()
                              └── delete()
```

Each `trace()`, `span()`, `generation()`, and `update()` call sends a request to the Langfuse `POST /api/public/ingestion` endpoint immediately. OTel ingestion posts to `POST /api/public/otel/v1/traces` once per `end()`.
**Langfuse PHP** was created by **[Tycho Engberink](https://github.com/tychoengberinkDIJ)** and is maintained by **[DIJ Digital](https://dij.digital)** under the **[MIT license](https://opensource.org/licenses/MIT)**.
