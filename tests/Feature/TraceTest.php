<?php

declare(strict_types=1);

use DIJ\Langfuse\PHP\Langfuse;
use DIJ\Langfuse\PHP\Responses\TraceListResponse;
use DIJ\Langfuse\PHP\Responses\TraceResponse;
use DIJ\Langfuse\PHP\Testing\Responses\GetTraceListResponse;
use DIJ\Langfuse\PHP\Testing\Responses\GetTraceResponse;
use DIJ\Langfuse\PHP\Transporters\HttpTransporter;
use DIJ\Langfuse\PHP\ValueObjects\MetaData;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;

it('can get a trace by id', function (): void {
    $mock = new MockHandler([
        new GetTraceResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $trace = (new Langfuse(new HttpTransporter($client)))
        ->trace()
        ->get('trace-abc123');

    expect($trace)->toBeInstanceOf(TraceResponse::class)
        ->and($trace->id)->toBe('trace-abc123')
        ->and($trace->name)->toBe('test-trace')
        ->and($trace->sessionId)->toBe('session-xyz789')
        ->and($trace->userId)->toBe('user-123')
        ->and($trace->projectId)->toBe('proj-abc123')
        ->and($trace->tags)->toBe(['production', 'test'])
        ->and($trace->totalCost)->toBe(0.0025)
        ->and($trace->latency)->toBe(1.5)
        ->and($trace->observations)->toBeArray()
        ->and($trace->scores)->toBeArray()
        ->and($trace->environment)->toBe('production');
});

it('can list traces', function (): void {
    $mock = new MockHandler([
        new GetTraceListResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $traces = (new Langfuse(new HttpTransporter($client)))
        ->trace()
        ->list();

    expect($traces)->toBeInstanceOf(TraceListResponse::class)
        ->and($traces->data)->toBeArray()
        ->and($traces->data)->toHaveCount(2)
        ->and($traces->data[0])->toBeInstanceOf(TraceResponse::class)
        ->and($traces->data[0]->id)->toBe('trace-abc123')
        ->and($traces->data[0]->name)->toBe('test-trace-1')
        ->and($traces->data[1]->id)->toBe('trace-def456')
        ->and($traces->data[1]->name)->toBe('test-trace-2')
        ->and($traces->meta)->toBeInstanceOf(MetaData::class)
        ->and($traces->meta->page)->toBe(1)
        ->and($traces->meta->limit)->toBe(10)
        ->and($traces->meta->totalItems)->toBe(2);
});

it('can list traces with filters', function (): void {
    $mock = new MockHandler([
        new GetTraceListResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $traces = (new Langfuse(new HttpTransporter($client)))
        ->trace()
        ->list(
            page: 1,
            limit: 10,
            userId: 'user-123',
            name: 'test-trace',
            sessionId: 'session-xyz789',
            environment: 'production',
        );

    expect($traces)->toBeInstanceOf(TraceListResponse::class)
        ->and($traces->data)->toBeArray();
});

it('can get trace with observations and scores', function (): void {
    $mock = new MockHandler([
        new GetTraceResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $trace = (new Langfuse(new HttpTransporter($client)))
        ->trace()
        ->get('trace-abc123');

    expect($trace->observations)->toBeArray()
        ->and($trace->observations)->not->toBeEmpty()
        ->and($trace->scores)->toBeArray()
        ->and($trace->scores)->not->toBeEmpty();

    /** @var array{id: string} $observation */
    $observation = $trace->observations[0];
    expect($observation['id'])->toBe('obs-001');

    /** @var array{id: string} $score */
    $score = $trace->scores[0];
    expect($score['id'])->toBe('score-001');
});
