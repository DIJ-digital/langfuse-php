<?php

declare(strict_types=1);

use DIJ\Langfuse\PHP\Langfuse;
use DIJ\Langfuse\PHP\Responses\SessionListResponse;
use DIJ\Langfuse\PHP\Responses\SessionResponse;
use DIJ\Langfuse\PHP\Testing\Responses\GetSessionListResponse;
use DIJ\Langfuse\PHP\Testing\Responses\GetSessionResponse;
use DIJ\Langfuse\PHP\Transporters\HttpTransporter;
use DIJ\Langfuse\PHP\ValueObjects\MetaData;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;

it('can get a session by id', function (): void {
    $mock = new MockHandler([
        new GetSessionResponse(),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $session = (new Langfuse(new HttpTransporter($client)))
        ->session()
        ->get('session-abc123');

    expect($session)->toBeInstanceOf(SessionResponse::class)
        ->and($session->id)->toBe('session-abc123')
        ->and($session->projectId)->toBe('proj-abc123')
        ->and($session->environment)->toBe('production')
        ->and($session->bookmarked)->toBe(false)
        ->and($session->public)->toBe(false)
        ->and($session->traces)->toBeArray()
        ->and($session->traces)->toHaveCount(2);
});

it('can list sessions', function (): void {
    $mock = new MockHandler([
        new GetSessionListResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $sessions = (new Langfuse(new HttpTransporter($client)))
        ->session()
        ->list();

    expect($sessions)->toBeInstanceOf(SessionListResponse::class)
        ->and($sessions->data)->toBeArray()
        ->and($sessions->data)->toHaveCount(2)
        ->and($sessions->data[0])->toBeInstanceOf(SessionResponse::class)
        ->and($sessions->data[0]->id)->toBe('session-abc123')
        ->and($sessions->data[1]->id)->toBe('session-def456')
        ->and($sessions->meta)->toBeInstanceOf(MetaData::class)
        ->and($sessions->meta->page)->toBe(1)
        ->and($sessions->meta->totalItems)->toBe(2);
});

it('can list sessions with filters', function (): void {
    $mock = new MockHandler([
        new GetSessionListResponse,
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $sessions = (new Langfuse(new HttpTransporter($client)))
        ->session()
        ->list(
            page: 1,
            limit: 10,
            fromTimestamp: '2025-01-01T00:00:00.000Z',
            toTimestamp: '2025-01-31T23:59:59.999Z',
            environment: ['production'],
        );

    expect($sessions)->toBeInstanceOf(SessionListResponse::class)
        ->and($sessions->data)->toBeArray();
});

it('can get session with traces', function (): void {
    $mock = new MockHandler([
        new GetSessionResponse(),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $session = (new Langfuse(new HttpTransporter($client)))
        ->session()
        ->get('session-abc123');

    expect($session->traces)->toBeArray()
        ->and($session->traces)->not->toBeEmpty();

    /** @var array{id: string, name: string} $trace */
    $trace = $session->traces[0];
    expect($trace['id'])->toBe('trace-001')
        ->and($trace['name'])->toBe('trace-in-session');
});
