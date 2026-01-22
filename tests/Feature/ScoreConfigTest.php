<?php

declare(strict_types=1);

use DIJ\Langfuse\PHP\Langfuse;
use DIJ\Langfuse\PHP\Responses\ScoreConfigListResponse;
use DIJ\Langfuse\PHP\Responses\ScoreConfigResponse;
use DIJ\Langfuse\PHP\Testing\Responses\GetScoreConfigListResponse;
use DIJ\Langfuse\PHP\Testing\Responses\GetScoreConfigResponse;
use DIJ\Langfuse\PHP\Testing\Responses\PostScoreConfigResponse;
use DIJ\Langfuse\PHP\Transporters\HttpTransporter;
use DIJ\Langfuse\PHP\ValueObjects\MetaData;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;

it('can get a score config by id', function (): void {
    $mock = new MockHandler([
        new GetScoreConfigResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $config = (new Langfuse(new HttpTransporter($client)))
        ->scoreConfig()
        ->get('config-abc123');

    expect($config)->toBeInstanceOf(ScoreConfigResponse::class)
        ->and($config->id)->toBe('config-abc123')
        ->and($config->name)->toBe('quality-score')
        ->and($config->dataType)->toBe('NUMERIC')
        ->and($config->isArchived)->toBe(false)
        ->and($config->minValue)->toBe(0.0)
        ->and($config->maxValue)->toBe(1.0)
        ->and($config->description)->toBe('Quality score for LLM outputs');
});

it('can list score configs', function (): void {
    $mock = new MockHandler([
        new GetScoreConfigListResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $configs = (new Langfuse(new HttpTransporter($client)))
        ->scoreConfig()
        ->list();

    expect($configs)->toBeInstanceOf(ScoreConfigListResponse::class)
        ->and($configs->data)->toBeArray()
        ->and($configs->data)->toHaveCount(2)
        ->and($configs->data[0])->toBeInstanceOf(ScoreConfigResponse::class)
        ->and($configs->data[0]->name)->toBe('quality-score')
        ->and($configs->data[0]->dataType)->toBe('NUMERIC')
        ->and($configs->data[1]->name)->toBe('sentiment')
        ->and($configs->data[1]->dataType)->toBe('CATEGORICAL')
        ->and($configs->meta)->toBeInstanceOf(MetaData::class)
        ->and($configs->meta->totalItems)->toBe(2);
});

it('can create a score config', function (): void {
    $mock = new MockHandler([
        new PostScoreConfigResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $config = (new Langfuse(new HttpTransporter($client)))
        ->scoreConfig()
        ->create(
            name: 'new-score-config',
            dataType: 'NUMERIC',
            minValue: 0.0,
            maxValue: 10.0,
            description: 'A new score configuration',
        );

    expect($config)->toBeInstanceOf(ScoreConfigResponse::class)
        ->and($config->id)->toBe('config-new123')
        ->and($config->name)->toBe('new-score-config')
        ->and($config->dataType)->toBe('NUMERIC')
        ->and($config->minValue)->toBe(0.0)
        ->and($config->maxValue)->toBe(10.0);
});

it('can get score config with categories', function (): void {
    $mock = new MockHandler([
        new GetScoreConfigListResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $configs = (new Langfuse(new HttpTransporter($client)))
        ->scoreConfig()
        ->list();

    $categoricalConfig = $configs->data[1];

    expect($categoricalConfig->categories)->toBeArray()
        ->and($categoricalConfig->categories)->toHaveCount(3)
        ->and($categoricalConfig->categories)->not->toBeNull();

    $categories = $categoricalConfig->categories;
    assert($categories !== null);

    /** @var array{value: float, label: string} $category */
    $category = $categories[0];
    expect($category['value'])->toEqual(1.0)
        ->and($category['label'])->toBe('positive');
});
