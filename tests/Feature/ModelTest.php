<?php

declare(strict_types=1);

use DIJ\Langfuse\PHP\Langfuse;
use DIJ\Langfuse\PHP\Responses\ModelListResponse;
use DIJ\Langfuse\PHP\Responses\ModelResponse;
use DIJ\Langfuse\PHP\Testing\Responses\DeleteModelResponse;
use DIJ\Langfuse\PHP\Testing\Responses\GetModelListResponse;
use DIJ\Langfuse\PHP\Testing\Responses\GetModelResponse;
use DIJ\Langfuse\PHP\Testing\Responses\PostModelResponse;
use DIJ\Langfuse\PHP\Transporters\HttpTransporter;
use DIJ\Langfuse\PHP\ValueObjects\MetaData;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;

it('can get a model by id', function (): void {
    $mock = new MockHandler([
        new GetModelResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $model = (new Langfuse(new HttpTransporter($client)))
        ->model()
        ->get('model-abc123');

    expect($model)->toBeInstanceOf(ModelResponse::class)
        ->and($model->id)->toBe('model-abc123')
        ->and($model->modelName)->toBe('gpt-4-turbo')
        ->and($model->matchPattern)->toBe('(?i)^gpt-4-turbo$')
        ->and($model->unit)->toBe('TOKENS')
        ->and($model->inputPrice)->toBe(0.00001)
        ->and($model->outputPrice)->toBe(0.00003)
        ->and($model->isLangfuseManaged)->toBe(false);
});

it('can list models', function (): void {
    $mock = new MockHandler([
        new GetModelListResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $models = (new Langfuse(new HttpTransporter($client)))
        ->model()
        ->list();

    expect($models)->toBeInstanceOf(ModelListResponse::class)
        ->and($models->data)->toBeArray()
        ->and($models->data)->toHaveCount(2)
        ->and($models->data[0])->toBeInstanceOf(ModelResponse::class)
        ->and($models->data[0]->modelName)->toBe('gpt-4-turbo')
        ->and($models->data[0]->isLangfuseManaged)->toBe(false)
        ->and($models->data[1]->modelName)->toBe('claude-3-opus')
        ->and($models->data[1]->isLangfuseManaged)->toBe(true)
        ->and($models->meta)->toBeInstanceOf(MetaData::class)
        ->and($models->meta->totalItems)->toBe(2);
});

it('can create a model', function (): void {
    $mock = new MockHandler([
        new PostModelResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $model = (new Langfuse(new HttpTransporter($client)))
        ->model()
        ->create(
            modelName: 'custom-model',
            matchPattern: '(?i)^custom-model$',
            unit: 'TOKENS',
            inputPrice: 0.00002,
            outputPrice: 0.00004,
        );

    expect($model)->toBeInstanceOf(ModelResponse::class)
        ->and($model->id)->toBe('model-new123')
        ->and($model->modelName)->toBe('custom-model')
        ->and($model->matchPattern)->toBe('(?i)^custom-model$')
        ->and($model->isLangfuseManaged)->toBe(false);
});

it('can delete a model', function (): void {
    $mock = new MockHandler([
        new DeleteModelResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $langfuse = new Langfuse(new HttpTransporter($client));
    $langfuse->model()->delete('model-abc123');

    expect($mock->count())->toBe(0);
});
