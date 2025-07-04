## Langfuse PHP - A PHP Client for Langfuse API
This package provides a wrapper around the [Langfuse](https://langfuse.com) Api, allowing you to easily integrate Langfuse into your PHP applications. It uses as few dependencies as possible.

### It supports the following features:
- Getting a text prompt
- Getting a chat prompt
- Compiling a text prompt
- Compiling a chat prompt
- Create a text prompt
- Create a chat prompt
- Fallbacks for prompt fetching when an error occurs
- Fallbacks for prompt fetching when no prompt is found
- Batch ingestion of events
- Single event ingestion with specific methods for each event type

> **Requires [PHP 8.3](https://php.net/releases/) or [PHP 8.4](https://php.net/releases/)**

⚡️ Install the package using **Composer**:
```bash  
composer require dij-digital/langfuse-php  
```  

🤙 Modern codebase , refactoring and static analysis in one command
```bash  
composer codestyle  
```  
🚀 Run the entire test suite:
```bash  
composer test  
```  

### How to use this package
```php
use DIJ\Langfuse\PHP;
use DIJ\Langfuse\PHP\Transporters\HttpTransporter;  
use DIJ\Langfuse\PHP\ValueObjects\IngestionEvent;
use GuzzleHttp\Client;

$langfuse = new Langfuse(new HttpTransporter(new Client([  
  'base_uri' => 'https://cloud.langfuse.com', //choose as needed
    'auth' => ['PUBLIC_KEY', 'SECRET_KEY'],  //generate a set in your project
])));

// Prompt operations
$langfuse->prompt()->text('promptName')->compile(['key' => 'value']);
$langfuse->prompt()->text('promptName')->compile(['key' => 'value']);
$langfuse->prompt()->chat('chatName')->compile(['key' => 'value']);
$langfuse->prompt()->list();
$langfuse->prompt()->create('promptName', 'text', PromptType::TEXT);

// Ingestion operations
$event = new IngestionEvent(
    id: 'trace-123',
    type: EventType::TRACE_CREATE,
    body: [
        'id' => 'trace-123',
        'name' => 'Test Trace',
        'timestamp' => '2024-01-01T00:00:00.000Z',
    ]
);

// Batch ingestion
$response = $langfuse->ingestion()->batch([$event]);

// Single event ingestion with specific methods
$response = $langfuse->ingestion()->trace(
    id: 'trace-123',
    body: [
        'id' => 'trace-123',
        'name' => 'Test Trace',
        'timestamp' => '2024-01-01T00:00:00.000Z',
    ]
);

$response = $langfuse->ingestion()->score(
    id: 'score-123',
    body: [
        'id' => 'score-123',
        'traceId' => 'trace-123',
        'name' => 'Test Score',
        'value' => 0.9,
    ]
);

$response = $langfuse->ingestion()->span(
    id: 'span-123',
    body: [
        'id' => 'span-123',
        'traceId' => 'trace-123',
        'name' => 'Test Span',
    ]
);

$response = $langfuse->ingestion()->generation(
    id: 'generation-123',
    body: [
        'id' => 'generation-123',
        'traceId' => 'trace-123',
        'name' => 'Test Generation',
        'model' => 'gpt-4',
        'input' => 'Hello world',
        'output' => 'Hello! How can I help you?',
    ]
);
```

**Langfuse PHP** was created by **[Tycho Engberink](https://dij.digital)** under the **[MIT license](https://opensource.org/licenses/MIT)**.
