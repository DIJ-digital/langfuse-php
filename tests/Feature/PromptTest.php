<?php

declare(strict_types=1);

use DIJ\Langfuse\PHP\Enums\PromptType;
use DIJ\Langfuse\PHP\Exceptions\InvalidPromptTypeException;
use DIJ\Langfuse\PHP\Langfuse;
use DIJ\Langfuse\PHP\Responses\ChatPromptResponse;
use DIJ\Langfuse\PHP\Responses\FallbackPrompt;
use DIJ\Langfuse\PHP\Responses\TextPromptResponse;
use DIJ\Langfuse\PHP\Testing\Responses\GetChatPromptResponse;
use DIJ\Langfuse\PHP\Testing\Responses\GetPromptListPageOneResponse;
use DIJ\Langfuse\PHP\Testing\Responses\GetPromptListPageTwoResponse;
use DIJ\Langfuse\PHP\Testing\Responses\GetPromptListResponse;
use DIJ\Langfuse\PHP\Testing\Responses\GetPromptResponse;
use DIJ\Langfuse\PHP\Testing\Responses\NoPromptFoundResponse;
use DIJ\Langfuse\PHP\Testing\Responses\PatchPromptLabelsResponse;
use DIJ\Langfuse\PHP\Testing\Responses\PostChatPromptResponse;
use DIJ\Langfuse\PHP\Testing\Responses\PostPromptResponse;
use DIJ\Langfuse\PHP\Transporters\HttpTransporter;
use DIJ\Langfuse\PHP\ValueObjects\PromptListItem;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;

it('can get a text prompt', function (): void {
    $mock = new MockHandler([
        new GetPromptResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $promptName = 'general_instructions';

    /** @var TextPromptResponse $prompt */
    $prompt = (new Langfuse(new HttpTransporter($client)))
        ->prompt()
        ->text($promptName);

    expect($prompt)->toBeInstanceOf(TextPromptResponse::class)
        ->and($prompt->type)->toBe('text')
        ->and($prompt->name)->toBe($promptName);
});

it('returns an error when chat prompt is provided when using text type', function (): void {
    $mock = new MockHandler([
        new GetChatPromptResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $promptName = 'generate_basic_report_input';

    (new Langfuse(new HttpTransporter($client)))
        ->prompt()
        ->text($promptName);
})->throws(InvalidPromptTypeException::class);

it('can list prompts', function (): void {
    $mock = new MockHandler([
        new GetPromptListResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $items = iterator_to_array((new Langfuse(new HttpTransporter($client)))->prompt()->list());

    expect($items)->toBeArray()
        ->not()->toBeEmpty()
        ->and($items[0])->toBeInstanceOf(PromptListItem::class)
        ->and($items[0]->name)->toBe('general_instructions');
});

it('returns null when prompt not found and no fallback is provided', function (): void {
    $mock = new MockHandler([
        new NoPromptFoundResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);
    $prompt = (new Langfuse(new HttpTransporter($client)))->prompt()->text('non-existent-prompt', fallback: null);

    expect($prompt)->toBeNull();
});

it('can get a chat prompt', function (): void {
    $mock = new MockHandler([
        new GetChatPromptResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $promptName = 'chat_test';

    /** @var ChatPromptResponse $prompt */
    $prompt = (new Langfuse(new HttpTransporter($client)))
        ->prompt()
        ->chat($promptName);

    expect($prompt)->toBeInstanceOf(ChatPromptResponse::class)
        ->and($prompt->type)->toBe('chat')
        ->and($prompt->name)->toBe($promptName);
});

it('can compile a text prompt', function (): void {
    $mock = new MockHandler([
        new GetPromptResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $promptName = 'generate_basic_report_input';

    /** @var TextPromptResponse $prompt */
    $prompt = (new Langfuse(new HttpTransporter($client)))
        ->prompt()
        ->text($promptName);

    $prompt = $prompt->compile(['name' => 'John Doe']);

    expect($prompt)->toBeString()
        ->toBe('You are a research bot John Doe');
});

it('returns an error when text prompt is provided when using text chat', function (): void {
    $mock = new MockHandler([
        new GetPromptResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $promptName = 'chat_test';

    (new Langfuse(new HttpTransporter($client)))
        ->prompt()
        ->chat($promptName);
})->throws(InvalidPromptTypeException::class);

it('can compile a chat prompt', function (): void {
    $mock = new MockHandler([
        new GetChatPromptResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $promptName = 'chat_test';

    /** @var ChatPromptResponse $prompt */
    $prompt = (new Langfuse(new HttpTransporter($client)))
        ->prompt()
        ->chat($promptName);

    $prompt = $prompt->compile(['name' => 'John Doe', 'user' => 'user']);

    expect($prompt)->toBeArray()
        ->toBe([
            ['role' => 'system', 'content' => 'Test John Doe'],
            ['role' => 'user', 'content' => 'test user'],
        ]);
});

it('can create a text prompt', function (): void {
    $mock = new MockHandler([
        new PostPromptResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $promptName = 'test';

    /** @var TextPromptResponse $prompt */
    $prompt = (new Langfuse(new HttpTransporter($client)))
        ->prompt()
        ->create($promptName, 'You are a research bot {{name}}', PromptType::TEXT);

    expect($prompt)->toBeInstanceOf(TextPromptResponse::class)
        ->and($prompt->type)->toBe('text')
        ->and($prompt->name)->toBe($promptName);
});

it('can create a chat prompt', function (): void {
    $mock = new MockHandler([
        new PostChatPromptResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $promptName = 'test';

    /** @var ChatPromptResponse $prompt */
    $prompt = (new Langfuse(new HttpTransporter($client)))
        ->prompt()
        ->create($promptName, [
            ['role' => 'system', 'content' => 'Test {{name}}'],
            ['role' => 'user', 'content' => 'test {{user}}'],
        ], PromptType::CHAT);

    expect($prompt)->toBeInstanceOf(ChatPromptResponse::class)
        ->and($prompt->type)->toBe('chat')
        ->and($prompt->name)->toBe($promptName);
});

it('uses fallback text prompt when prompt not found', function (): void {
    $mock = new MockHandler([
        new NoPromptFoundResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $promptName = 'non-existent-prompt';
    $fallback = 'This is a fallback prompt for {{name}}';

    /** @var TextPromptResponse $prompt */
    $prompt = (new Langfuse(new HttpTransporter($client)))
        ->prompt()
        ->text($promptName, fallback: $fallback);

    expect($prompt)->toBeInstanceOf(FallbackPrompt::class)
        ->and($prompt->prompt)->toBe($fallback)
        ->and($prompt->type)->toBe(PromptType::TEXT->value);
});

it('uses fallback chat prompt when prompt not found', function (): void {
    $mock = new MockHandler([
        new NoPromptFoundResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $promptName = 'non-existent-chat-prompt';
    $fallback = [
        ['role' => 'system', 'content' => 'Fallback system message'],
        ['role' => 'user', 'content' => 'Fallback user message for {{name}}'],
    ];

    /** @var ChatPromptResponse $prompt */
    $prompt = (new Langfuse(new HttpTransporter($client)))
        ->prompt()
        ->chat($promptName, fallback: $fallback);

    expect($prompt)->toBeInstanceOf(FallbackPrompt::class)
        ->and($prompt->type)->toBe(PromptType::CHAT->value)
        ->and($prompt->prompt)->toBe($fallback);
});

it('uses fallback text prompt when connection error occurs', function (): void {
    $mock = new MockHandler([
        new GuzzleHttp\Exception\ConnectException('Connection failed', new GuzzleHttp\Psr7\Request('GET', 'test')),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $promptName = 'unreachable-prompt';
    $fallback = 'Connection failed, using fallback: {{message}}';

    /** @var TextPromptResponse $prompt */
    $prompt = (new Langfuse(new HttpTransporter($client)))
        ->prompt()
        ->text($promptName, fallback: $fallback);

    expect($prompt)->toBeInstanceOf(FallbackPrompt::class)
        ->and($prompt->type)->toBe(PromptType::TEXT->value)
        ->and($prompt->prompt)->toBe($fallback);
});

it('can compile fallback text prompt', function (): void {
    $mock = new MockHandler([
        new NoPromptFoundResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $promptName = 'non-existent-prompt';
    $fallback = 'Hello {{name}}, this is a fallback prompt';

    /** @var TextPromptResponse $prompt */
    $prompt = (new Langfuse(new HttpTransporter($client)))
        ->prompt()
        ->text($promptName, fallback: $fallback);

    $compiled = $prompt->compile(['name' => 'John']);

    expect($compiled)->toBe('Hello John, this is a fallback prompt');
});

it('can compile fallback chat prompt', function (): void {
    $mock = new MockHandler([
        new NoPromptFoundResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $promptName = 'non-existent-chat-prompt';
    $fallback = [
        ['role' => 'system', 'content' => 'You are {{role}}'],
        ['role' => 'user', 'content' => 'Hello {{name}}'],
    ];

    /** @var ChatPromptResponse $prompt */
    $prompt = (new Langfuse(new HttpTransporter($client)))
        ->prompt()
        ->chat($promptName, fallback: $fallback);

    $compiled = $prompt->compile(['role' => 'assistant', 'name' => 'Alice']);

    expect($compiled)->toBe([
        ['role' => 'system', 'content' => 'You are assistant'],
        ['role' => 'user', 'content' => 'Hello Alice'],
    ]);
});

it('can list all prompts across multiple pages', function (): void {
    $mock = new MockHandler([
        new GetPromptListPageOneResponse,
        new GetPromptListPageTwoResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $items = iterator_to_array(
        (new Langfuse(new HttpTransporter($client)))->prompt()->list()
    );

    expect($items)->toHaveCount(3)
        ->and($items[0])->toBeInstanceOf(PromptListItem::class)
        ->and($items[0]->name)->toBe('general_instructions')
        ->and($items[1]->name)->toBe('generate_basic_report_input')
        ->and($items[2]->name)->toBe('generate_search_terms');
});

it('can list all prompts when there is only one page', function (): void {
    $mock = new MockHandler([
        new GetPromptListResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $items = iterator_to_array(
        (new Langfuse(new HttpTransporter($client)))->prompt()->list()
    );

    expect($items)->toHaveCount(5)
        ->and($items[0])->toBeInstanceOf(PromptListItem::class);
});

it('can update prompt labels', function (): void {
    $mock = new MockHandler([
        new PatchPromptLabelsResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $prompt = (new Langfuse(new HttpTransporter($client)))
        ->prompt()
        ->update('general_instructions', 1, ['staging', 'latest']);

    expect($prompt)->toBeInstanceOf(TextPromptResponse::class)
        ->and($prompt->name)->toBe('general_instructions')
        ->and($prompt->labels)->toBe(['staging', 'latest']);
});

it('can update prompt labels on a chat prompt', function (): void {
    $mock = new MockHandler([
        new PatchPromptLabelsResponse(data: [
            'type' => 'chat',
            'name' => 'chat_prompt',
            'prompt' => [
                ['role' => 'system', 'content' => 'You are helpful'],
            ],
            'labels' => ['production'],
        ]),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $prompt = (new Langfuse(new HttpTransporter($client)))
        ->prompt()
        ->update('chat_prompt', 1, ['production']);

    expect($prompt)->toBeInstanceOf(ChatPromptResponse::class)
        ->and($prompt->labels)->toBe(['production']);
});
