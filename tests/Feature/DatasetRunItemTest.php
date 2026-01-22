<?php

declare(strict_types=1);

use DIJ\Langfuse\PHP\Langfuse;
use DIJ\Langfuse\PHP\Responses\DatasetRunItemListResponse;
use DIJ\Langfuse\PHP\Responses\DatasetRunItemResponse;
use DIJ\Langfuse\PHP\Testing\Responses\GetDatasetRunItemListResponse;
use DIJ\Langfuse\PHP\Testing\Responses\GetDatasetRunItemResponse;
use DIJ\Langfuse\PHP\Transporters\HttpTransporter;
use DIJ\Langfuse\PHP\ValueObjects\MetaData;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;

it('can create a dataset run item', function (): void {
    $mock = new MockHandler([
        new GetDatasetRunItemResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $item = (new Langfuse(new HttpTransporter($client)))
        ->datasetRunItem()
        ->create(
            runName: 'test-run',
            datasetItemId: 'item-789',
            traceId: 'trace-abc',
        );

    expect($item)->toBeInstanceOf(DatasetRunItemResponse::class)
        ->and($item->id)->toBe('run-item-123')
        ->and($item->datasetRunId)->toBe('run-456')
        ->and($item->datasetRunName)->toBe('test-run')
        ->and($item->datasetItemId)->toBe('item-789')
        ->and($item->traceId)->toBe('trace-abc');
});

it('can list dataset run items', function (): void {
    $mock = new MockHandler([
        new GetDatasetRunItemListResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $items = (new Langfuse(new HttpTransporter($client)))
        ->datasetRunItem()
        ->list(
            datasetId: 'dataset-123',
            runName: 'test-run',
        );

    expect($items)->toBeInstanceOf(DatasetRunItemListResponse::class)
        ->and($items->data)->toBeArray()
        ->and($items->data)->toHaveCount(2)
        ->and($items->data[0])->toBeInstanceOf(DatasetRunItemResponse::class)
        ->and($items->data[0]->id)->toBe('run-item-123')
        ->and($items->data[1]->id)->toBe('run-item-456')
        ->and($items->data[1]->observationId)->toBe('obs-xyz')
        ->and($items->meta)->toBeInstanceOf(MetaData::class)
        ->and($items->meta->totalItems)->toBe(2);
});

it('can list dataset run items with pagination', function (): void {
    /** @var array<int, array{request: RequestInterface}> $history */
    $history = [];

    $mock = new MockHandler([
        new GetDatasetRunItemListResponse,
    ]);

    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($history));

    $client = new Client(['handler' => $stack]);

    (new Langfuse(new HttpTransporter($client)))
        ->datasetRunItem()
        ->list(
            datasetId: 'dataset-123',
            runName: 'test-run',
            page: 2,
            limit: 10,
        );

    expect($history)->toHaveCount(1);

    /** @var array{request: RequestInterface} $first */
    $first = $history[0];
    /** @var RequestInterface $request */
    $request = $first['request'];
    $query = $request->getUri()->getQuery();

    expect($query)->toContain('datasetId=dataset-123')
        ->and($query)->toContain('runName=test-run')
        ->and($query)->toContain('page=2')
        ->and($query)->toContain('limit=10');
});

it('sends correct payload when creating a dataset run item', function (): void {
    /** @var array<int, array{request: RequestInterface}> $history */
    $history = [];

    $mock = new MockHandler([
        new GetDatasetRunItemResponse,
    ]);

    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($history));

    $client = new Client(['handler' => $stack]);

    (new Langfuse(new HttpTransporter($client)))
        ->datasetRunItem()
        ->create(
            runName: 'my-run',
            datasetItemId: 'item-abc',
            traceId: 'trace-123',
            observationId: 'obs-456',
            runDescription: 'Test run description',
            metadata: ['env' => 'test'],
        );

    expect($history)->toHaveCount(1);

    /** @var array{request: RequestInterface} $first */
    $first = $history[0];
    /** @var RequestInterface $request */
    $request = $first['request'];

    expect($request->getMethod())->toBe('POST')
        ->and((string) $request->getUri())->toContain('/api/public/dataset-run-items')
        ->and($request->getHeaderLine('Content-Type'))->toContain('application/json');

    /** @var array<string, mixed> $body */
    $body = json_decode((string) $request->getBody(), true);

    expect($body['runName'])->toBe('my-run')
        ->and($body['datasetItemId'])->toBe('item-abc')
        ->and($body['traceId'])->toBe('trace-123')
        ->and($body['observationId'])->toBe('obs-456')
        ->and($body['runDescription'])->toBe('Test run description')
        ->and($body['metadata'])->toBe(['env' => 'test']);
});
