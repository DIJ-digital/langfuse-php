## Langfuse PHP - A PHP Client for Langfuse API
This package provides a wrapper around the [Langfuse](https://langfuse.com) Api, allowing you to easily integrate Langfuse into your PHP applications. It uses as few dependencies as possible.

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
- Buffered flush via [OTLP](https://langfuse.com/docs/integrations/native/opentelemetry)

> **Requires [PHP 8.3](https://php.net/releases/) or [PHP 8.4](https://php.net/releases/)**

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
    serviceName: 'my-app', // optional, used as OTLP service.name resource attribute
);
```

#### Prompts
```php
// Get and compile prompts
$langfuse->prompt()->text(promptName: 'promptName')->compile(params: ['key' => 'value']);
$langfuse->prompt()->chat(promptName: 'chatName')->compile(params: ['key' => 'value']);

// List all prompts (auto-paginated)
$langfuse->prompt()->list();

// Create a prompt
$langfuse->prompt()->create(promptName: 'promptName', prompt: 'text', type: PromptType::TEXT);

// Update prompt labels
$langfuse->prompt()->updateLabels(name: 'promptName', version: 1, labels: ['production']);
```

#### Ingestion

The ingestion API follows the same pattern as the [Langfuse Python SDK](https://langfuse.com/docs/sdk/python/low-level-sdk). All operations are buffered in memory -- nothing is sent until you call `flush()` (or the `Ingestion` object is destroyed). One `flush()` serializes everything into a single [OTLP](https://langfuse.com/docs/integrations/native/opentelemetry) HTTP request.

```php
$ingestion = $langfuse->ingestion(environment: 'production'); // optional, defaults to 'default'
```

##### Trace

A trace is the root of an observation tree. Creating a trace buffers it in memory and returns a `Trace` object.

```php
$trace = $ingestion->trace(
    name: 'my-workflow',
    userId: 'user-456',
    input: 'user question',
);

// Update the trace (mutates in-memory, nothing sent yet)
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

// Update and close spans when work is done
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

##### Flushing

Call `flush()` to send all buffered spans in a single HTTP request. The destructor also calls `flush()` as a safety net.

```php
$ingestion->flush();
```

##### Full example

```php
$ingestion = $langfuse->ingestion(environment: 'production');

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

// Send everything in one HTTP call
$ingestion->flush();
```

### Architecture

```
Langfuse(transporter, serviceName?)
├── prompt()                    → Prompt
└── ingestion(environment?)     → Ingestion
                                  ├── trace()      → Trace
                                  │                   ├── update()
                                  │                   ├── span()       → Span
                                  │                   └── generation() → Generation
                                  ├── span()       → Span
                                  │                   ├── update()
                                  │                   ├── span()       → Span
                                  │                   └── generation() → Generation
                                  ├── generation() → Generation
                                  │                   └── update()
                                  └── flush()
```

All `trace()`, `span()`, `generation()`, and `update()` calls mutate in-memory state only. Call `flush()` to serialize everything into a single OTLP HTTP request to the Langfuse `/api/public/otel/v1/traces` endpoint. The `Ingestion` destructor calls `flush()` automatically as a safety net.

**Langfuse PHP** was created by **[Tycho Engberink](https://github.com/tychoengberinkDIJ)** and is maintained by **[DIJ Digital](https://dij.digital)** under the **[MIT license](https://opensource.org/licenses/MIT)**.
