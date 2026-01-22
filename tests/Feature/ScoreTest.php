<?php

declare(strict_types=1);

use DIJ\Langfuse\PHP\Enums\ScoreDataType;
use DIJ\Langfuse\PHP\Enums\ScoreSource;
use DIJ\Langfuse\PHP\Langfuse;
use DIJ\Langfuse\PHP\Responses\ScoreListResponse;
use DIJ\Langfuse\PHP\Responses\ScoreResponse;
use DIJ\Langfuse\PHP\Testing\Responses\GetScoreListResponse;
use DIJ\Langfuse\PHP\Testing\Responses\GetScoreResponse;
use DIJ\Langfuse\PHP\Transporters\HttpTransporter;
use DIJ\Langfuse\PHP\ValueObjects\MetaData;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

it('can create a numeric score', function (): void {
    $mock = new MockHandler([
        new GetScoreResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $score = (new Langfuse(new HttpTransporter($client)))
        ->score()
        ->create(
            traceId: 'trace-456',
            name: 'accuracy',
            value: 0.95,
            dataType: ScoreDataType::NUMERIC,
        );

    expect($score)->toBeInstanceOf(ScoreResponse::class)
        ->and($score->id)->toBe('score-123')
        ->and($score->traceId)->toBe('trace-456')
        ->and($score->name)->toBe('accuracy')
        ->and($score->value)->toBe(0.95)
        ->and($score->dataType)->toBe(ScoreDataType::NUMERIC)
        ->and($score->source)->toBe(ScoreSource::API);
});

it('can create a categorical score', function (): void {
    $mock = new MockHandler([
        new GetScoreResponse(data: [
            'value' => 'helpful',
            'dataType' => 'CATEGORICAL',
            'stringValue' => 'helpful',
        ]),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $score = (new Langfuse(new HttpTransporter($client)))
        ->score()
        ->create(
            traceId: 'trace-456',
            name: 'helpfulness',
            value: 'helpful',
            dataType: ScoreDataType::CATEGORICAL,
        );

    expect($score)->toBeInstanceOf(ScoreResponse::class)
        ->and($score->dataType)->toBe(ScoreDataType::CATEGORICAL)
        ->and($score->value)->toBe('helpful')
        ->and($score->stringValue)->toBe('helpful');
});

it('can create a boolean score', function (): void {
    $mock = new MockHandler([
        new GetScoreResponse(data: [
            'value' => 1,
            'dataType' => 'BOOLEAN',
            'stringValue' => 'True',
        ]),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $score = (new Langfuse(new HttpTransporter($client)))
        ->score()
        ->create(
            traceId: 'trace-456',
            name: 'is_correct',
            value: 1,
            dataType: ScoreDataType::BOOLEAN,
        );

    expect($score)->toBeInstanceOf(ScoreResponse::class)
        ->and($score->dataType)->toBe(ScoreDataType::BOOLEAN);
});

it('can get a score by id', function (): void {
    $mock = new MockHandler([
        new GetScoreResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $score = (new Langfuse(new HttpTransporter($client)))
        ->score()
        ->get('score-123');

    expect($score)->toBeInstanceOf(ScoreResponse::class)
        ->and($score->id)->toBe('score-123')
        ->and($score->comment)->toBe('Good accuracy');
});

it('can list scores', function (): void {
    $mock = new MockHandler([
        new GetScoreListResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $scores = (new Langfuse(new HttpTransporter($client)))
        ->score()
        ->list();

    expect($scores)->toBeInstanceOf(ScoreListResponse::class)
        ->and($scores->data)->toBeArray()
        ->and($scores->data)->toHaveCount(2)
        ->and($scores->data[0])->toBeInstanceOf(ScoreResponse::class)
        ->and($scores->data[0]->name)->toBe('accuracy')
        ->and($scores->data[1]->name)->toBe('helpfulness')
        ->and($scores->meta)->toBeInstanceOf(MetaData::class)
        ->and($scores->meta->totalItems)->toBe(2);
});

it('can list scores with filters', function (): void {
    /** @var array<int, array{request: RequestInterface}> $history */
    $history = [];

    $mock = new MockHandler([
        new GetScoreListResponse,
    ]);

    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($history));

    $client = new Client(['handler' => $stack]);

    (new Langfuse(new HttpTransporter($client)))
        ->score()
        ->list(
            page: 1,
            limit: 10,
            name: 'accuracy',
            dataType: ScoreDataType::NUMERIC,
            traceId: 'trace-123',
        );

    expect($history)->toHaveCount(1);

    /** @var array{request: RequestInterface} $first */
    $first = $history[0];
    /** @var RequestInterface $request */
    $request = $first['request'];
    $query = $request->getUri()->getQuery();

    expect($query)->toContain('page=1')
        ->and($query)->toContain('limit=10')
        ->and($query)->toContain('name=accuracy')
        ->and($query)->toContain('dataType=NUMERIC')
        ->and($query)->toContain('traceId=trace-123');
});

it('can delete a score', function (): void {
    /** @var array<int, array{request: RequestInterface}> $history */
    $history = [];

    $mock = new MockHandler([
        new Response(204),
    ]);

    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($history));

    $client = new Client(['handler' => $stack]);

    (new Langfuse(new HttpTransporter($client)))
        ->score()
        ->delete('score-123');

    expect($history)->toHaveCount(1);

    /** @var array{request: RequestInterface} $first */
    $first = $history[0];
    /** @var RequestInterface $request */
    $request = $first['request'];

    expect($request->getMethod())->toBe('DELETE')
        ->and((string) $request->getUri())->toContain('/api/public/scores/score-123');
});

it('sends correct payload when creating a score', function (): void {
    /** @var array<int, array{request: RequestInterface}> $history */
    $history = [];

    $mock = new MockHandler([
        new GetScoreResponse,
    ]);

    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($history));

    $client = new Client(['handler' => $stack]);

    (new Langfuse(new HttpTransporter($client)))
        ->score()
        ->create(
            traceId: 'trace-456',
            name: 'accuracy',
            value: 0.95,
            dataType: ScoreDataType::NUMERIC,
            id: 'custom-score-id',
            observationId: 'obs-789',
            comment: 'Test comment',
            configId: 'config-abc',
        );

    expect($history)->toHaveCount(1);

    /** @var array{request: RequestInterface} $first */
    $first = $history[0];
    /** @var RequestInterface $request */
    $request = $first['request'];

    expect($request->getMethod())->toBe('POST')
        ->and((string) $request->getUri())->toContain('/api/public/scores')
        ->and($request->getHeaderLine('Content-Type'))->toContain('application/json');

    /** @var array<string, mixed> $body */
    $body = json_decode((string) $request->getBody(), true);

    expect($body['traceId'])->toBe('trace-456')
        ->and($body['name'])->toBe('accuracy')
        ->and($body['value'])->toBe(0.95)
        ->and($body['dataType'])->toBe('NUMERIC')
        ->and($body['id'])->toBe('custom-score-id')
        ->and($body['observationId'])->toBe('obs-789')
        ->and($body['comment'])->toBe('Test comment')
        ->and($body['configId'])->toBe('config-abc');
});
