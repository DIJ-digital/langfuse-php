<?php

declare(strict_types=1);

use DIJ\Langfuse\Langfuse;
use DIJ\Langfuse\Responses\PromptListResponse;
use DIJ\Langfuse\Responses\PromptResponse;
use DIJ\Langfuse\Transporters\HttpTransporter;
use DIJ\Langfuse\ValueObjects\MetaData;
use DIJ\Langfuse\ValueObjects\PaginationData;
use DIJ\Langfuse\ValueObjects\PromptListItem;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use Tests\Fixtures\Prompts\GetPromptListResponse;
use Tests\Fixtures\Prompts\GetPromptResponse;
use Tests\Fixtures\Prompts\NoPromptFoundResponse;

it('can get a text prompt', function (): void {
    $mock = new MockHandler([
        new GetPromptResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);
    $prompt = new Langfuse(new HttpTransporter($client))
        ->prompt()
        ->text('generate_basic_report_input');

    expect($prompt)->toBeInstanceOf(PromptResponse::class);
    expect($prompt->type)->toBe('text');
});

it('can list prompts', function (): void {
    $mock = new MockHandler([
        new GetPromptListResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);
    $prompts = new Langfuse(new HttpTransporter($client))->prompt()->list();

    expect($prompts)->toBeInstanceOf(PromptListResponse::class)
        ->and($prompts->data)->toBeArray()
        ->and($prompts->data)->not->toBeEmpty()
        ->and($prompts->data[0])->toBeInstanceOf(PromptListItem::class)
        ->and($prompts->meta)->toBeInstanceOf(MetaData::class)
        ->and($prompts->meta->page)->toBeNumeric()
        ->and($prompts->meta->limit)->toBeNumeric()
        ->and($prompts->meta->totalPages)->toBeNumeric()
        ->and($prompts->meta->totalItems)->toBeNumeric()
        ->and($prompts->pagination)->toBeInstanceOf(PaginationData::class)
        ->and($prompts->pagination->page)->toBeNumeric()
        ->and($prompts->pagination->limit)->toBeNumeric()
        ->and($prompts->pagination->totalPages)->toBeNumeric()
        ->and($prompts->pagination->totalItems)->toBeNumeric();

});

it('returns null when prompt not found', function (): void {
    $mock = new MockHandler([
        new NoPromptFoundResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);
    $prompt = new Langfuse(new HttpTransporter($client))->prompt()->text('non-existent-prompt');

    expect($prompt)->toBeNull();
});



it('can get a chat prompt')->todo();
it('can compile a text prompt')->todo();
it('can compile a chat prompt')->todo();
it('can create a text prompt')->todo();
it('can create a chat prompt')->todo();
