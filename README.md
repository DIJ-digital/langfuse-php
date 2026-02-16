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
    environment: 'production', // optional, defaults to 'default'
);
```

#### Prompts

```php
// Get and compile prompts
$langfuse->prompt()->text(promptName: 'promptName')->compile(params: ['key' => 'value']);
$langfuse->prompt()->chat(promptName: 'chatName')->compile(params: ['key' => 'value']);

// List all prompts (auto-paginated Generator)
foreach ($langfuse->prompt()->list() as $item) {
    echo $item->name;
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

### Architecture

```
Langfuse(transporter, environment?)
├── prompt()                → Prompt
│                                 ├── text()     → TextPromptResponse|FallbackPrompt
│                                 ├── chat()     → ChatPromptResponse|FallbackPrompt
│                                 ├── list()     → Generator<PromptListItem>
│                                 ├── create()   → TextPromptResponse|ChatPromptResponse
│                                 └── update()   → TextPromptResponse|ChatPromptResponse
└── ingestion()             → Ingestion
                              ├── trace()      → Trace
                              │                   ├── update()
                              │                   ├── span()       → Span
                              │                   └── generation() → Generation
                              ├── span()       → Span
                              │                   ├── update()
                              │                   ├── span()       → Span
                              │                   └── generation() → Generation
                              └── generation() → Generation
                                                  └── update()
```

Each `trace()`, `span()`, `generation()`, and `update()` call sends a request to the Langfuse `POST /api/public/ingestion` endpoint immediately.

**Langfuse PHP** was created by **[Tycho Engberink](https://github.com/tychoengberinkDIJ)** and is maintained by **[DIJ Digital](https://dij.digital)** under the **[MIT license](https://opensource.org/licenses/MIT)**.
