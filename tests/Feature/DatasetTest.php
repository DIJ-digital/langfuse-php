<?php

declare(strict_types=1);

use DIJ\Langfuse\PHP\Langfuse;
use DIJ\Langfuse\PHP\Responses\DatasetListResponse;
use DIJ\Langfuse\PHP\Responses\DatasetResponse;
use DIJ\Langfuse\PHP\Responses\DatasetRunListResponse;
use DIJ\Langfuse\PHP\Responses\DatasetRunResponse;
use DIJ\Langfuse\PHP\Testing\Responses\GetDatasetListResponse;
use DIJ\Langfuse\PHP\Testing\Responses\GetDatasetResponse;
use DIJ\Langfuse\PHP\Testing\Responses\GetDatasetRunListResponse;
use DIJ\Langfuse\PHP\Transporters\HttpTransporter;
use DIJ\Langfuse\PHP\ValueObjects\MetaData;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;

it('can create a dataset', function (): void {
    $mock = new MockHandler([
        new GetDatasetResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $dataset = (new Langfuse(new HttpTransporter($client)))
        ->dataset()
        ->create(
            name: 'test-dataset',
            description: 'A test dataset',
            metadata: ['key' => 'value'],
        );

    expect($dataset)->toBeInstanceOf(DatasetResponse::class)
        ->and($dataset->id)->toBe('dataset-123')
        ->and($dataset->name)->toBe('test-dataset')
        ->and($dataset->description)->toBe('A test dataset')
        ->and($dataset->projectId)->toBe('project-456')
        ->and($dataset->metadata)->toBe(['key' => 'value']);
});

it('can get a dataset by name', function (): void {
    $mock = new MockHandler([
        new GetDatasetResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $dataset = (new Langfuse(new HttpTransporter($client)))
        ->dataset()
        ->get('test-dataset');

    expect($dataset)->toBeInstanceOf(DatasetResponse::class)
        ->and($dataset->id)->toBe('dataset-123')
        ->and($dataset->name)->toBe('test-dataset');
});

it('can list datasets', function (): void {
    $mock = new MockHandler([
        new GetDatasetListResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $datasets = (new Langfuse(new HttpTransporter($client)))
        ->dataset()
        ->list();

    expect($datasets)->toBeInstanceOf(DatasetListResponse::class)
        ->and($datasets->data)->toBeArray()
        ->and($datasets->data)->toHaveCount(2)
        ->and($datasets->data[0])->toBeInstanceOf(DatasetResponse::class)
        ->and($datasets->data[0]->name)->toBe('test-dataset')
        ->and($datasets->data[1]->name)->toBe('another-dataset')
        ->and($datasets->meta)->toBeInstanceOf(MetaData::class)
        ->and($datasets->meta->totalItems)->toBe(2);
});

it('can list datasets with pagination', function (): void {
    /** @var array<int, array{request: RequestInterface}> $history */
    $history = [];

    $mock = new MockHandler([
        new GetDatasetListResponse,
    ]);

    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($history));

    $client = new Client(['handler' => $stack]);

    (new Langfuse(new HttpTransporter($client)))
        ->dataset()
        ->list(page: 2, limit: 10);

    expect($history)->toHaveCount(1);

    /** @var array{request: RequestInterface} $first */
    $first = $history[0];
    /** @var RequestInterface $request */
    $request = $first['request'];
    $query = $request->getUri()->getQuery();

    expect($query)->toContain('page=2')
        ->and($query)->toContain('limit=10');
});

it('can get dataset runs', function (): void {
    $mock = new MockHandler([
        new GetDatasetRunListResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $runs = (new Langfuse(new HttpTransporter($client)))
        ->dataset()
        ->getRuns('test-dataset');

    expect($runs)->toBeInstanceOf(DatasetRunListResponse::class)
        ->and($runs->data)->toBeArray()
        ->and($runs->data)->toHaveCount(2)
        ->and($runs->data[0])->toBeInstanceOf(DatasetRunResponse::class)
        ->and($runs->data[0]->name)->toBe('test-run')
        ->and($runs->data[0]->datasetId)->toBe('dataset-123')
        ->and($runs->data[0]->datasetName)->toBe('test-dataset')
        ->and($runs->data[1]->name)->toBe('another-run')
        ->and($runs->meta)->toBeInstanceOf(MetaData::class)
        ->and($runs->meta->totalItems)->toBe(2);
});

it('sends correct payload when creating a dataset', function (): void {
    /** @var array<int, array{request: RequestInterface}> $history */
    $history = [];

    $mock = new MockHandler([
        new GetDatasetResponse,
    ]);

    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($history));

    $client = new Client(['handler' => $stack]);

    (new Langfuse(new HttpTransporter($client)))
        ->dataset()
        ->create(
            name: 'my-dataset',
            description: 'Test description',
            metadata: ['env' => 'test'],
            inputSchema: ['type' => 'object'],
            expectedOutputSchema: ['type' => 'string'],
        );

    expect($history)->toHaveCount(1);

    /** @var array{request: RequestInterface} $first */
    $first = $history[0];
    /** @var RequestInterface $request */
    $request = $first['request'];

    expect($request->getMethod())->toBe('POST')
        ->and((string) $request->getUri())->toContain('/api/public/v2/datasets')
        ->and($request->getHeaderLine('Content-Type'))->toContain('application/json');

    /** @var array<string, mixed> $body */
    $body = json_decode((string) $request->getBody(), true);

    expect($body['name'])->toBe('my-dataset')
        ->and($body['description'])->toBe('Test description')
        ->and($body['metadata'])->toBe(['env' => 'test'])
        ->and($body['inputSchema'])->toBe(['type' => 'object'])
        ->and($body['expectedOutputSchema'])->toBe(['type' => 'string']);
});
