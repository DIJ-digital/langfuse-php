<?php

declare(strict_types=1);

use DIJ\Langfuse\PHP\Enums\DatasetStatus;
use DIJ\Langfuse\PHP\Langfuse;
use DIJ\Langfuse\PHP\Responses\DatasetItemListResponse;
use DIJ\Langfuse\PHP\Responses\DatasetItemResponse;
use DIJ\Langfuse\PHP\Testing\Responses\GetDatasetItemListResponse;
use DIJ\Langfuse\PHP\Testing\Responses\GetDatasetItemResponse;
use DIJ\Langfuse\PHP\Transporters\HttpTransporter;
use DIJ\Langfuse\PHP\ValueObjects\MetaData;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

it('can create a dataset item', function (): void {
    $mock = new MockHandler([
        new GetDatasetItemResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $item = (new Langfuse(new HttpTransporter($client)))
        ->datasetItem()
        ->create(
            datasetName: 'test-dataset',
            input: ['prompt' => 'What is AI?'],
            expectedOutput: ['response' => 'AI is artificial intelligence'],
            metadata: ['key' => 'value'],
        );

    expect($item)->toBeInstanceOf(DatasetItemResponse::class)
        ->and($item->id)->toBe('item-123')
        ->and($item->status)->toBe(DatasetStatus::ACTIVE)
        ->and($item->datasetId)->toBe('dataset-456')
        ->and($item->datasetName)->toBe('test-dataset')
        ->and($item->input)->toBe(['prompt' => 'What is AI?'])
        ->and($item->expectedOutput)->toBe(['response' => 'AI is artificial intelligence'])
        ->and($item->metadata)->toBe(['key' => 'value']);
});

it('can get a dataset item by id', function (): void {
    $mock = new MockHandler([
        new GetDatasetItemResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $item = (new Langfuse(new HttpTransporter($client)))
        ->datasetItem()
        ->get('item-123');

    expect($item)->toBeInstanceOf(DatasetItemResponse::class)
        ->and($item->id)->toBe('item-123')
        ->and($item->datasetName)->toBe('test-dataset');
});

it('can list dataset items', function (): void {
    $mock = new MockHandler([
        new GetDatasetItemListResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $items = (new Langfuse(new HttpTransporter($client)))
        ->datasetItem()
        ->list();

    expect($items)->toBeInstanceOf(DatasetItemListResponse::class)
        ->and($items->data)->toBeArray()
        ->and($items->data)->toHaveCount(2)
        ->and($items->data[0])->toBeInstanceOf(DatasetItemResponse::class)
        ->and($items->data[0]->id)->toBe('item-123')
        ->and($items->data[1]->id)->toBe('item-456')
        ->and($items->meta)->toBeInstanceOf(MetaData::class)
        ->and($items->meta->totalItems)->toBe(2);
});

it('can list dataset items with filters', function (): void {
    /** @var array<int, array{request: RequestInterface}> $history */
    $history = [];

    $mock = new MockHandler([
        new GetDatasetItemListResponse,
    ]);

    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($history));

    $client = new Client(['handler' => $stack]);

    (new Langfuse(new HttpTransporter($client)))
        ->datasetItem()
        ->list(
            datasetName: 'test-dataset',
            sourceTraceId: 'trace-123',
            page: 1,
            limit: 10,
        );

    expect($history)->toHaveCount(1);

    /** @var array{request: RequestInterface} $first */
    $first = $history[0];
    /** @var RequestInterface $request */
    $request = $first['request'];
    $query = $request->getUri()->getQuery();

    expect($query)->toContain('datasetName=test-dataset')
        ->and($query)->toContain('sourceTraceId=trace-123')
        ->and($query)->toContain('page=1')
        ->and($query)->toContain('limit=10');
});

it('can delete a dataset item', function (): void {
    /** @var array<int, array{request: RequestInterface}> $history */
    $history = [];

    $mock = new MockHandler([
        new Response(204),
    ]);

    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($history));

    $client = new Client(['handler' => $stack]);

    (new Langfuse(new HttpTransporter($client)))
        ->datasetItem()
        ->delete('item-123');

    expect($history)->toHaveCount(1);

    /** @var array{request: RequestInterface} $first */
    $first = $history[0];
    /** @var RequestInterface $request */
    $request = $first['request'];

    expect($request->getMethod())->toBe('DELETE')
        ->and((string) $request->getUri())->toContain('/api/public/dataset-items/item-123');
});

it('sends correct payload when creating a dataset item', function (): void {
    /** @var array<int, array{request: RequestInterface}> $history */
    $history = [];

    $mock = new MockHandler([
        new GetDatasetItemResponse,
    ]);

    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($history));

    $client = new Client(['handler' => $stack]);

    (new Langfuse(new HttpTransporter($client)))
        ->datasetItem()
        ->create(
            datasetName: 'my-dataset',
            input: ['question' => 'test'],
            expectedOutput: ['answer' => 'response'],
            metadata: ['env' => 'test'],
            sourceTraceId: 'trace-789',
            sourceObservationId: 'obs-123',
            id: 'custom-item-id',
            status: DatasetStatus::ACTIVE,
        );

    expect($history)->toHaveCount(1);

    /** @var array{request: RequestInterface} $first */
    $first = $history[0];
    /** @var RequestInterface $request */
    $request = $first['request'];

    expect($request->getMethod())->toBe('POST')
        ->and((string) $request->getUri())->toContain('/api/public/dataset-items')
        ->and($request->getHeaderLine('Content-Type'))->toContain('application/json');

    /** @var array<string, mixed> $body */
    $body = json_decode((string) $request->getBody(), true);

    expect($body['datasetName'])->toBe('my-dataset')
        ->and($body['input'])->toBe(['question' => 'test'])
        ->and($body['expectedOutput'])->toBe(['answer' => 'response'])
        ->and($body['metadata'])->toBe(['env' => 'test'])
        ->and($body['sourceTraceId'])->toBe('trace-789')
        ->and($body['sourceObservationId'])->toBe('obs-123')
        ->and($body['id'])->toBe('custom-item-id')
        ->and($body['status'])->toBe('ACTIVE');
});
