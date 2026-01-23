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
- Fallback handling for prompt fetching errors
- Fallback handling when no prompt is found

#### Ingestion
- Create traces
- Create generations

#### Scores
- Create scores
- Get scores
- List scores
- Delete scores
- V2 API support for scores

> **Requires [PHP 8.3](https://php.net/releases/) or [PHP 8.4](https://php.net/releases/)**

⚡️ Install the package using **Composer**:
```bash  
composer require dij-digital/langfuse-php  
```  

### How to use this package

#### Prompt
```php
use DIJ\Langfuse\PHP;
use DIJ\Langfuse\PHP\Transporters\HttpTransporter;  
use GuzzleHttp\Client;

$langfuse = new Langfuse(new HttpTransporter(new Client([  
  'base_uri' => 'https://cloud.langfuse.com', //choose as needed
    'auth' => ['PUBLIC_KEY', 'SECRET_KEY'],  //generate a set in your project
])));

$langfuse->prompt()->text(promptName: 'promptName')->compile(params: ['key' => 'value']);
$langfuse->prompt()->text(promptName: 'promptName')->compile(params: ['key' => 'value']);
$langfuse->prompt()->chat(promptName: 'chatName')->compile(params: ['key' => 'value']);
$langfuse->prompt()->list();
$langfuse->prompt()->create(promptName: 'promptName', prompt: 'text', type: PromptType::TEXT);
```

#### Ingestion
```php
use DIJ\Langfuse\PHP;

// Creates a trace and a generation visible in Langfuse UI
$traceId = 'trace-id-123';

$langfuse->ingestion()->trace(
    input: 'prompt text',
    output: null,
    traceId: $traceId,
    name: 'name',
    sessionId: null,
    metadata: ['key' => 'value']
);

$langfuse->ingestion()->generation(
    input: 'prompt text',
    output: 'model output',
    traceId: $traceId,
    name: 'name',
    sessionId: null,
    promptName: 'promptName',
    promptVersion: 1,
    model: 'gpt-4o',
    modelParameters: ['temperature' => 0.7],
    metadata: ['key' => 'value']
);
```

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

**Langfuse PHP** was created by **[Tycho Engberink](https://github.com/tychoengberinkDIJ)** and is maintained by **[DIJ Digital](https://dij.digital)** under the **[MIT license](https://opensource.org/licenses/MIT)**.
