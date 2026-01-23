<?php

declare(strict_types=1);

use DIJ\Langfuse\PHP\Langfuse;
use DIJ\Langfuse\PHP\Responses\ObservationListResponse;
use DIJ\Langfuse\PHP\Responses\ObservationResponse;
use DIJ\Langfuse\PHP\Testing\Responses\GetObservationListResponse;
use DIJ\Langfuse\PHP\Testing\Responses\GetObservationResponse;
use DIJ\Langfuse\PHP\Transporters\HttpTransporter;
use DIJ\Langfuse\PHP\ValueObjects\MetaData;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;

it('can get an observation by id', function (): void {
    $mock = new MockHandler([
        new GetObservationResponse(),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $observation = (new Langfuse(new HttpTransporter($client)))
        ->observation()
        ->get('obs-abc123');

    expect($observation)->toBeInstanceOf(ObservationResponse::class)
        ->and($observation->id)->toBe('obs-abc123')
        ->and($observation->traceId)->toBe('trace-abc123')
        ->and($observation->type)->toBe('GENERATION')
        ->and($observation->name)->toBe('llm-generation')
        ->and($observation->model)->toBe('gpt-4o')
        ->and($observation->latency)->toBe(2.5)
        ->and($observation->environment)->toBe('production');
});

it('can list observations', function (): void {
    $mock = new MockHandler([
        new GetObservationListResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $observations = (new Langfuse(new HttpTransporter($client)))
        ->observation()
        ->list();

    expect($observations)->toBeInstanceOf(ObservationListResponse::class)
        ->and($observations->data)->toBeArray()
        ->and($observations->data)->toHaveCount(2)
        ->and($observations->data[0])->toBeInstanceOf(ObservationResponse::class)
        ->and($observations->data[0]->id)->toBe('obs-abc123')
        ->and($observations->data[0]->type)->toBe('GENERATION')
        ->and($observations->data[1]->id)->toBe('obs-def456')
        ->and($observations->data[1]->type)->toBe('SPAN')
        ->and($observations->meta)->toBeInstanceOf(MetaData::class)
        ->and($observations->meta->totalItems)->toBe(2);
});

it('can list observations with filters', function (): void {
    /** @var array<int, array{request: RequestInterface}> $history */
    $history = [];

    $mock = new MockHandler([
        new GetObservationListResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $handlerStack->push(Middleware::history($history));
    $client = new Client(['handler' => $handlerStack]);

    $observations = (new Langfuse(new HttpTransporter($client)))
        ->observation()
        ->list(
            page: 1,
            limit: 10,
            traceId: 'trace-abc123',
            type: 'GENERATION',
            environment: ['production'],
        );

    expect($observations)->toBeInstanceOf(ObservationListResponse::class)
        ->and($observations->data)->toBeArray();

    expect($history)->toHaveCount(1);

    /** @var array{request: RequestInterface} $first */
    $first = $history[0];
    /** @var RequestInterface $request */
    $request = $first['request'];
    $query = $request->getUri()->getQuery();

    expect($query)->toContain('page=1')
        ->and($query)->toContain('limit=10')
        ->and($query)->toContain('traceId=trace-abc123')
        ->and($query)->toContain('type=GENERATION');
});

it('can get observation with usage data', function (): void {
    $mock = new MockHandler([
        new GetObservationResponse(),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $observation = (new Langfuse(new HttpTransporter($client)))
        ->observation()
        ->get('obs-abc123');

    expect($observation->usage)->toBeArray()
        ->and($observation->usage)->not->toBeEmpty()
        ->and($observation->calculatedTotalCost)->toBe(0.0006)
        ->and($observation->promptName)->toBe('greeting-prompt')
        ->and($observation->promptVersion)->toBe(1);

    /** @var array{input: int, output: int, total: int} $usage */
    $usage = $observation->usage;
    expect($usage['input'])->toBe(10)
        ->and($usage['output'])->toBe(25)
        ->and($usage['total'])->toBe(35);
});
