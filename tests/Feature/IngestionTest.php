<?php

declare(strict_types=1);

use DIJ\Langfuse\PHP\Exceptions\LangfuseException;
use DIJ\Langfuse\PHP\Ingestion;
use DIJ\Langfuse\PHP\Ingestion\Generation;
use DIJ\Langfuse\PHP\Ingestion\Span;
use DIJ\Langfuse\PHP\Ingestion\Trace;
use DIJ\Langfuse\PHP\Transporters\HttpTransporter;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Create an Ingestion instance with a mock HTTP transport that captures requests.
 *
 * @param array<int, array{request: RequestInterface, response: ResponseInterface, options: array<string, mixed>}> $history
 */
function makeIngestion(array &$history, int $responseCount = 10): Ingestion
{
    $mock = new MockHandler(array_fill(0, $responseCount, new Response(207)));
    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($history)); // @phpstan-ignore parameterByRef.type
    $client = new Client(['base_uri' => 'https://example.test', 'handler' => $stack]);

    return new Ingestion(
        transporter: new HttpTransporter($client),
        environment: 'testing',
    );
}

/**
 * Get the ingestion payload from a captured request.
 *
 * @param array<int, array{request: RequestInterface, response: ResponseInterface, options: array<string, mixed>}> $history
 * @return array<string, mixed>
 */
function getPayload(array $history, int $index = 0): array
{
    /** @var array<string, mixed> $body */
    $body = json_decode((string) $history[$index]['request']->getBody(), true, 512, JSON_THROW_ON_ERROR);

    return $body;
}

/**
 * Get the first event body from a captured request.
 *
 * @param array<int, array{request: RequestInterface, response: ResponseInterface, options: array<string, mixed>}> $history
 * @return array<string, mixed>
 */
function getEventBody(array $history, int $index = 0): array
{
    $payload = getPayload($history, $index);

    /** @var array{batch: list<array{body: array<string, mixed>}>} $payload */
    return $payload['batch'][0]['body'];
}

/**
 * Get the event type from a captured request.
 *
 * @param array<int, array{request: RequestInterface, response: ResponseInterface, options: array<string, mixed>}> $history
 */
function getEventType(array $history, int $index = 0): string
{
    $payload = getPayload($history, $index);

    /** @var array{batch: list<array{type: string}>} $payload */
    return $payload['batch'][0]['type'];
}

// ─── Trace ──────────────────────────────────────────────────────────────────

it('creates a trace and posts to ingestion endpoint', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeIngestion($history);

    // Act
    $trace = $ingestion->trace(
        name: 'test-trace',
        traceId: 'my-trace-id',
        input: 'hello',
    );

    // Assert
    expect($trace)->toBeInstanceOf(Trace::class)
        ->and($trace->id)->toBe('my-trace-id')
        ->and($history)->toHaveCount(1);

    /** @var RequestInterface $request */
    $request = $history[0]['request'];
    expect($request->getMethod())->toBe('POST')
        ->and((string) $request->getUri())->toContain('/api/public/ingestion');

    $body = getEventBody($history);
    expect(getEventType($history))->toBe('trace-create')
        ->and($body['id'])->toBe('my-trace-id')
        ->and($body['name'])->toBe('test-trace')
        ->and($body['input'])->toBe('hello')
        ->and($body['environment'])->toBe('testing');
});

it('creates a trace with userId and sessionId', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeIngestion($history);

    // Act
    $ingestion->trace(name: 'test-trace', sessionId: 'sess-456', userId: 'user-123');

    // Assert
    $body = getEventBody($history);
    expect($body['userId'])->toBe('user-123')
        ->and($body['sessionId'])->toBe('sess-456');
});

it('creates a trace with metadata and tags', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeIngestion($history);

    // Act
    $ingestion->trace(
        name: 'test-trace',
        metadata: ['source' => 'test'],
        tags: ['tag1', 'tag2'],
    );

    // Assert
    $body = getEventBody($history);
    expect($body['metadata'])->toBe(['source' => 'test'])
        ->and($body['tags'])->toBe(['tag1', 'tag2']);
});

it('omits null values from trace body', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeIngestion($history);

    // Act
    $ingestion->trace(name: 'test-trace');

    // Assert
    $body = getEventBody($history);
    expect($body)->not->toHaveKey('userId')
        ->and($body)->not->toHaveKey('sessionId')
        ->and($body)->not->toHaveKey('input')
        ->and($body)->not->toHaveKey('output')
        ->and($body)->not->toHaveKey('metadata')
        ->and($body)->not->toHaveKey('tags');
});

// ─── Trace update ───────────────────────────────────────────────────────────

it('updates a trace with a second POST', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeIngestion($history);

    // Act
    $trace = $ingestion->trace(name: 'my-trace', input: 'start');
    $result = $trace->update(userId: 'user-456', output: 'final result');

    // Assert
    expect($result)->toBe($trace)
        ->and($history)->toHaveCount(2);

    expect(getEventType($history, index: 1))->toBe('trace-create');

    $body = getEventBody($history, index: 1);
    expect($body['id'])->toBe($trace->id)
        ->and($body['output'])->toBe('final result')
        ->and($body['userId'])->toBe('user-456');
});

// ─── Span ───────────────────────────────────────────────────────────────────

it('creates a span and returns a Span', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeIngestion($history);

    // Act
    $span = $ingestion->span(
        traceId: 'my-trace-id',
        name: 'web-search-batch',
        input: ['query' => 'test'],
    );

    // Assert
    expect($span)->toBeInstanceOf(Span::class)
        ->and($span->id)->toBeString()
        ->and($history)->toHaveCount(1);

    $body = getEventBody($history);
    expect(getEventType($history))->toBe('span-create')
        ->and($body['traceId'])->toBe('my-trace-id')
        ->and($body['name'])->toBe('web-search-batch')
        ->and($body['input'])->toBe(['query' => 'test']);
});

it('creates a span with a provided spanId', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeIngestion($history);

    // Act
    $span = $ingestion->span(
        traceId: 'my-trace-id',
        name: 'my-span',
        spanId: 'custom-span-id',
    );

    // Assert
    expect($span->id)->toBe('custom-span-id');

    $body = getEventBody($history);
    expect($body['id'])->toBe('custom-span-id');
});

it('updates a span with span-update type', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeIngestion($history);

    // Act
    $span = $ingestion->span(traceId: 'my-trace-id', name: 'search-span');
    $result = $span->update(output: ['results' => 3], endTime: '2025-06-01T12:00:00+00:00');

    // Assert
    expect($result)->toBe($span)
        ->and($history)->toHaveCount(2);

    expect(getEventType($history, index: 1))->toBe('span-update');

    $body = getEventBody($history, index: 1);
    expect($body['id'])->toBe($span->id)
        ->and($body['output'])->toBe(['results' => 3])
        ->and($body['endTime'])->toBe('2025-06-01T12:00:00+00:00');
});

// ─── Generation ─────────────────────────────────────────────────────────────

it('creates a generation with full payload', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeIngestion($history);

    // Act
    $gen = $ingestion->generation(
        traceId: 'my-trace-id',
        name: 'test-generation',
        input: ['messages' => [['role' => 'user', 'content' => 'Hi']]],
        output: 'Hello',
        model: 'gpt-4o',
        modelParameters: ['temperature' => 0.2],
        promptName: 'prompt-x',
        promptVersion: 3,
        metadata: ['source' => 'test'],
    );

    // Assert
    expect($gen)->toBeInstanceOf(Generation::class)
        ->and($history)->toHaveCount(1);

    $body = getEventBody($history);
    expect(getEventType($history))->toBe('generation-create')
        ->and($body['traceId'])->toBe('my-trace-id')
        ->and($body['name'])->toBe('test-generation')
        ->and($body['input'])->toBe(['messages' => [['role' => 'user', 'content' => 'Hi']]])
        ->and($body['output'])->toBe('Hello')
        ->and($body['model'])->toBe('gpt-4o')
        ->and($body['promptName'])->toBe('prompt-x')
        ->and($body['promptVersion'])->toBe(3)
        ->and($body['modelParameters'])->toBe(['temperature' => 0.2])
        ->and($body['metadata'])->toBe(['source' => 'test']);
});

it('creates a generation with parentObservationId', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeIngestion($history);

    // Act
    $ingestion->generation(
        traceId: 'my-trace-id',
        name: 'gen',
        input: 'prompt',
        output: 'response',
        parentObservationId: 'span-abc',
    );

    // Assert
    $body = getEventBody($history);
    expect($body['parentObservationId'])->toBe('span-abc');
});

it('updates a generation with generation-update type', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeIngestion($history);

    // Act
    $gen = $ingestion->generation(
        traceId: 'my-trace-id',
        name: 'gen',
        input: 'prompt',
        output: 'initial',
    );
    $gen->update(output: 'updated response', model: 'gpt-4o');

    // Assert
    expect($history)->toHaveCount(2);

    expect(getEventType($history, index: 1))->toBe('generation-update');

    $body = getEventBody($history, index: 1);
    expect($body['id'])->toBe($gen->id)
        ->and($body['output'])->toBe('updated response')
        ->and($body['model'])->toBe('gpt-4o');
});

// ─── Trace child spawning ───────────────────────────────────────────────────

it('creates a span from a trace', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeIngestion($history);

    // Act
    $trace = $ingestion->trace(name: 'my-trace');
    $span = $trace->span(name: 'child-span');

    // Assert
    expect($span)->toBeInstanceOf(Span::class)
        ->and($history)->toHaveCount(2);

    $body = getEventBody($history, index: 1);
    expect($body['traceId'])->toBe($trace->id)
        ->and($body['name'])->toBe('child-span');
});

it('creates a generation from a trace', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeIngestion($history);

    // Act
    $trace = $ingestion->trace(name: 'my-trace');
    $gen = $trace->generation(name: 'llm-call', input: 'prompt', output: 'response');

    // Assert
    expect($gen)->toBeInstanceOf(Generation::class)
        ->and($history)->toHaveCount(2);

    $body = getEventBody($history, index: 1);
    expect(getEventType($history, index: 1))->toBe('generation-create')
        ->and($body['traceId'])->toBe($trace->id)
        ->and($body['name'])->toBe('llm-call');
});

// ─── Span child spawning ───────────────────────────────────────────────────

it('creates a child span from a span with parentObservationId', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeIngestion($history);

    // Act
    $trace = $ingestion->trace(name: 'my-trace');
    $parent = $trace->span(name: 'parent-span');
    $child = $parent->span(name: 'child-span');

    // Assert
    expect($history)->toHaveCount(3);

    $body = getEventBody($history, index: 2);
    expect($body['traceId'])->toBe($trace->id)
        ->and($body['parentObservationId'])->toBe($parent->id)
        ->and($body['name'])->toBe('child-span');
});

it('creates a generation from a span with parentObservationId', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeIngestion($history);

    // Act
    $trace = $ingestion->trace(name: 'my-trace');
    $span = $trace->span(name: 'my-span');
    $gen = $span->generation(name: 'llm-call', input: 'prompt', output: 'response');

    // Assert
    expect($history)->toHaveCount(3);

    $body = getEventBody($history, index: 2);
    expect(getEventType($history, index: 2))->toBe('generation-create')
        ->and($body['traceId'])->toBe($trace->id)
        ->and($body['parentObservationId'])->toBe($span->id);
});

// ─── Payload structure ─────────────────────────────────────────────────────

it('sends correct v2 ingestion batch structure', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeIngestion($history);

    // Act
    $ingestion->trace(name: 'my-trace');

    // Assert
    $payload = getPayload($history);

    expect($payload)->toHaveKey('batch')
        ->and($payload['batch'])->toHaveCount(1);

    /** @var array{batch: list<array{id: string, timestamp: string, type: string, body: array<string, mixed>}>} $payload */
    $event = $payload['batch'][0];
    expect($event)->toHaveKey('id')
        ->and($event)->toHaveKey('timestamp')
        ->and($event)->toHaveKey('type')
        ->and($event)->toHaveKey('body')
        ->and($event['type'])->toBe('trace-create');
});

it('generates valid uuid v4 format', function (): void {
    // Act
    $uuid = Ingestion::uuid();

    // Assert
    expect($uuid)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/');
});

// ─── Error handling ─────────────────────────────────────────────────────────

it('throws when ingestion responds with error', function (): void {
    // Arrange
    $mock = new MockHandler([new Response(500)]);
    $stack = HandlerStack::create($mock);
    $client = new Client(['base_uri' => 'https://example.test', 'handler' => $stack]);

    $ingestion = new Ingestion(
        transporter: new HttpTransporter($client),
        environment: 'testing',
    );

    // Act & Assert
    $ingestion->trace(name: 'err');
})->throws(LangfuseException::class);

// ─── Full example ───────────────────────────────────────────────────────────

it('handles a full trace with spans and generations', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeIngestion($history, responseCount: 20);

    // Act
    $trace = $ingestion->trace(name: 'handle-request', userId: 'user-789', input: 'What is the weather?');
    $span = $trace->span(name: 'search-batch');
    $child = $span->span(name: 'weather-api-call');
    $child->update(output: ['temp' => 22], endTime: '2025-06-01T12:00:00+00:00');
    $gen = $span->generation(name: 'summarize', input: 'Summarize weather data', output: 'It is 22 degrees.', model: 'gpt-4o');
    $span->update(output: ['answer' => 'It is 22 degrees.'], endTime: '2025-06-01T12:00:01+00:00');
    $trace->update(output: 'It is 22 degrees and sunny.');

    // Assert: 7 HTTP calls (trace, span, child-span, child-update, generation, span-update, trace-update)
    expect($history)->toHaveCount(7);

    expect(getEventType($history, index: 0))->toBe('trace-create')
        ->and(getEventType($history, index: 1))->toBe('span-create')
        ->and(getEventType($history, index: 2))->toBe('span-create')
        ->and(getEventType($history, index: 3))->toBe('span-update')
        ->and(getEventType($history, index: 4))->toBe('generation-create')
        ->and(getEventType($history, index: 5))->toBe('span-update')
        ->and(getEventType($history, index: 6))->toBe('trace-create');
});
