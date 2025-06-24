------  
## Langfuse PHP - A PHP Client for Langfuse API
This package provides a wrapper around the [Langfuse](https://langfuse.com) Api, allowing you to easily integrate Langfuse into your PHP applications. It uses as few dependencies as possible.

### It supports the following features:
- Getting a text prompt
- Getting a chat prompt
- Compiling a text prompt
- Compiling a chat prompt
- Create a text prompt
- Create a chat prompt

> **Requires [PHP 8.4](https://php.net/releases/)**

⚡️ Install the package using **Composer**:
```bash  
composer require dij/langfuse-php  
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
use GuzzleHttp\Client;

$langfuse = new Langfuse(new HttpTransporter(new Client([  
  'base_uri' => 'https://cloud.langfuse.com', //choose as needed
    'auth' => ['PUBLIC_KEY', 'SECRET_KEY'],  //generate a set in your project
])));

$langfuse->prompt()->text('promptName')->compile(['key' => 'value']);
$langfuse->prompt()->text('promptName')->compile(['key' => 'value']);
$langfuse->prompt()->chat('chatName')->compile(['key' => 'value']);
$langfuse->prompt()->list();
$langfuse->prompt()->create('promptName', 'text', PromptType::TEXT);
```

**Langfuse PHP** was created by **[Tycho Engberink](https://dij.digital)** under the **[MIT license](https://opensource.org/licenses/MIT)**.
