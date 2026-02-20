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

it('creates a trace with all parameters', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeIngestion($history);

    // Act
    $trace = $ingestion->trace(
        name: 'test-trace',
        traceId: 'my-trace-id',
        sessionId: 'sess-456',
        userId: 'user-123',
        input: 'hello',
        output: 'world',
        metadata: ['source' => 'test'],
        tags: ['tag1', 'tag2'],
        release: '1.2.3',
        version: '2.0.0',
        public: true,
    );

    // Assert
    /** @var RequestInterface $request */
    $request = $history[0]['request'];
    $body = getEventBody($history);

    expect($trace)->toBeInstanceOf(Trace::class)
        ->and($trace->id)->toBe('my-trace-id')
        ->and($history)->toHaveCount(1)
        ->and($request->getMethod())->toBe('POST')
        ->and((string) $request->getUri())->toContain('/api/public/ingestion')
        ->and(getEventType($history))->toBe('trace-create')
        ->and($body['id'])->toBe('my-trace-id')
        ->and($body['name'])->toBe('test-trace')
        ->and($body['userId'])->toBe('user-123')
        ->and($body['sessionId'])->toBe('sess-456')
        ->and($body['input'])->toBe('hello')
        ->and($body['output'])->toBe('world')
        ->and($body['metadata'])->toBe(['source' => 'test'])
        ->and($body['tags'])->toBe(['tag1', 'tag2'])
        ->and($body['release'])->toBe('1.2.3')
        ->and($body['version'])->toBe('2.0.0')
        ->and($body['public'])->toBeTrue()
        ->and($body['environment'])->toBe('testing');
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
        ->and($body)->not->toHaveKey('tags')
        ->and($body)->not->toHaveKey('release')
        ->and($body)->not->toHaveKey('version')
        ->and($body)->not->toHaveKey('public');
});

it('updates a trace with all parameters', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeIngestion($history);

    // Act
    $trace = $ingestion->trace(name: 'my-trace');
    $result = $trace->update(
        output: 'final result',
        userId: 'user-456',
        release: '1.0.0',
        version: '3.0.0',
        public: false,
    );

    // Assert
    $body = getEventBody($history, index: 1);
    expect($result)->toBe($trace)
        ->and($history)->toHaveCount(2)
        ->and(getEventType($history, index: 1))->toBe('trace-create')
        ->and($body['id'])->toBe($trace->id)
        ->and($body['output'])->toBe('final result')
        ->and($body['userId'])->toBe('user-456')
        ->and($body['release'])->toBe('1.0.0')
        ->and($body['version'])->toBe('3.0.0')
        ->and($body['public'])->toBeFalse();
});

// ─── Span ───────────────────────────────────────────────────────────────────

it('creates a span with all parameters', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeIngestion($history);

    // Act
    $span = $ingestion->span(
        traceId: 'my-trace-id',
        name: 'web-search-batch',
        spanId: 'custom-span-id',
        parentObservationId: 'parent-123',
        input: ['query' => 'test'],
        output: 'result',
        startTime: '2025-01-01T00:00:00Z',
        endTime: '2025-01-01T00:00:02Z',
        metadata: ['source' => 'test'],
        level: 'WARNING',
        statusMessage: 'Something went wrong',
        version: '1.0.0',
    );

    // Assert
    $body = getEventBody($history);
    expect($span)->toBeInstanceOf(Span::class)
        ->and($span->id)->toBe('custom-span-id')
        ->and($history)->toHaveCount(1)
        ->and(getEventType($history))->toBe('span-create')
        ->and($body['id'])->toBe('custom-span-id')
        ->and($body['traceId'])->toBe('my-trace-id')
        ->and($body['parentObservationId'])->toBe('parent-123')
        ->and($body['name'])->toBe('web-search-batch')
        ->and($body['input'])->toBe(['query' => 'test'])
        ->and($body['output'])->toBe('result')
        ->and($body['startTime'])->toBe('2025-01-01T00:00:00Z')
        ->and($body['endTime'])->toBe('2025-01-01T00:00:02Z')
        ->and($body['metadata'])->toBe(['source' => 'test'])
        ->and($body['level'])->toBe('WARNING')
        ->and($body['statusMessage'])->toBe('Something went wrong')
        ->and($body['version'])->toBe('1.0.0');
});

it('updates a span with all parameters', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeIngestion($history);

    // Act
    $span = $ingestion->span(traceId: 'my-trace-id', name: 'search-span');
    $result = $span->update(
        output: ['results' => 3],
        endTime: '2025-06-01T12:00:00+00:00',
        level: 'ERROR',
        statusMessage: 'Failed',
        version: '1.1.0',
    );

    // Assert
    $body = getEventBody($history, index: 1);
    expect($result)->toBe($span)
        ->and($history)->toHaveCount(2)
        ->and(getEventType($history, index: 1))->toBe('span-update')
        ->and($body['id'])->toBe($span->id)
        ->and($body['output'])->toBe(['results' => 3])
        ->and($body['endTime'])->toBe('2025-06-01T12:00:00+00:00')
        ->and($body['level'])->toBe('ERROR')
        ->and($body['statusMessage'])->toBe('Failed')
        ->and($body['version'])->toBe('1.1.0');
});

// ─── Generation ─────────────────────────────────────────────────────────────

it('creates a generation with all parameters', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeIngestion($history);

    // Act
    $gen = $ingestion->generation(
        traceId: 'my-trace-id',
        name: 'test-generation',
        input: ['messages' => [['role' => 'user', 'content' => 'Hi']]],
        output: 'Hello',
        generationId: 'custom-gen-id',
        parentObservationId: 'span-abc',
        model: 'gpt-4o',
        modelParameters: ['temperature' => 0.2],
        promptName: 'prompt-x',
        promptVersion: 3,
        metadata: ['source' => 'test'],
        startTime: '2025-01-01T00:00:00Z',
        endTime: '2025-01-01T00:00:05Z',
        completionStartTime: '2025-01-01T00:00:03Z',
        usageDetails: ['input' => 10, 'output' => 20, 'total' => 30],
        costDetails: ['input' => 0.001, 'output' => 0.002, 'total' => 0.003],
        level: 'DEBUG',
        statusMessage: 'All good',
        version: '2.0.0',
    );

    // Assert
    $body = getEventBody($history);
    expect($gen)->toBeInstanceOf(Generation::class)
        ->and($gen->id)->toBe('custom-gen-id')
        ->and($history)->toHaveCount(1)
        ->and(getEventType($history))->toBe('generation-create')
        ->and($body['id'])->toBe('custom-gen-id')
        ->and($body['traceId'])->toBe('my-trace-id')
        ->and($body['parentObservationId'])->toBe('span-abc')
        ->and($body['name'])->toBe('test-generation')
        ->and($body['input'])->toBe(['messages' => [['role' => 'user', 'content' => 'Hi']]])
        ->and($body['output'])->toBe('Hello')
        ->and($body['model'])->toBe('gpt-4o')
        ->and($body['modelParameters'])->toBe(['temperature' => 0.2])
        ->and($body['promptName'])->toBe('prompt-x')
        ->and($body['promptVersion'])->toBe(3)
        ->and($body['metadata'])->toBe(['source' => 'test'])
        ->and($body['startTime'])->toBe('2025-01-01T00:00:00Z')
        ->and($body['endTime'])->toBe('2025-01-01T00:00:05Z')
        ->and($body['completionStartTime'])->toBe('2025-01-01T00:00:03Z')
        ->and($body['usageDetails'])->toBe(['input' => 10, 'output' => 20, 'total' => 30])
        ->and($body['costDetails'])->toBe(['input' => 0.001, 'output' => 0.002, 'total' => 0.003])
        ->and($body['level'])->toBe('DEBUG')
        ->and($body['statusMessage'])->toBe('All good')
        ->and($body['version'])->toBe('2.0.0');
});

it('updates a generation with all parameters', function (): void {
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
    $result = $gen->update(
        output: 'final',
        model: 'gpt-4o',
        endTime: '2025-01-01T00:00:05Z',
        completionStartTime: '2025-01-01T00:00:03Z',
        usageDetails: ['input' => 15, 'output' => 25, 'total' => 40],
        costDetails: ['total' => 0.004],
        level: 'WARNING',
        statusMessage: 'Slow response',
        version: '1.1.0',
    );

    // Assert
    $body = getEventBody($history, index: 1);
    expect($result)->toBe($gen)
        ->and($history)->toHaveCount(2)
        ->and(getEventType($history, index: 1))->toBe('generation-update')
        ->and($body['id'])->toBe($gen->id)
        ->and($body['output'])->toBe('final')
        ->and($body['model'])->toBe('gpt-4o')
        ->and($body['endTime'])->toBe('2025-01-01T00:00:05Z')
        ->and($body['completionStartTime'])->toBe('2025-01-01T00:00:03Z')
        ->and($body['usageDetails'])->toBe(['input' => 15, 'output' => 25, 'total' => 40])
        ->and($body['costDetails'])->toBe(['total' => 0.004])
        ->and($body['level'])->toBe('WARNING')
        ->and($body['statusMessage'])->toBe('Slow response')
        ->and($body['version'])->toBe('1.1.0');
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
    $body = getEventBody($history, index: 1);
    expect($span)->toBeInstanceOf(Span::class)
        ->and($history)->toHaveCount(2)
        ->and($body['traceId'])->toBe($trace->id)
        ->and($body['name'])->toBe('child-span');
});

it('creates a generation from a trace with usageDetails and costDetails', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeIngestion($history);

    // Act
    $trace = $ingestion->trace(name: 'my-trace');
    $gen = $trace->generation(
        name: 'llm-call',
        input: 'prompt',
        output: 'response',
        model: 'gpt-4o',
        usageDetails: ['input' => 10, 'output' => 20],
        costDetails: ['total' => 0.002],
    );

    // Assert
    $body = getEventBody($history, index: 1);
    expect($gen)->toBeInstanceOf(Generation::class)
        ->and($history)->toHaveCount(2)
        ->and(getEventType($history, index: 1))->toBe('generation-create')
        ->and($body['traceId'])->toBe($trace->id)
        ->and($body['name'])->toBe('llm-call')
        ->and($body['usageDetails'])->toBe(['input' => 10, 'output' => 20])
        ->and($body['costDetails'])->toBe(['total' => 0.002]);
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
    $body = getEventBody($history, index: 2);
    expect($history)->toHaveCount(3)
        ->and($body['traceId'])->toBe($trace->id)
        ->and($body['parentObservationId'])->toBe($parent->id)
        ->and($body['name'])->toBe('child-span');
});

it('creates a generation from a span with usageDetails and costDetails', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeIngestion($history);

    // Act
    $trace = $ingestion->trace(name: 'my-trace');
    $span = $trace->span(name: 'my-span');
    $gen = $span->generation(
        name: 'llm-call',
        input: 'prompt',
        output: 'response',
        usageDetails: ['input' => 5, 'output' => 10],
        costDetails: ['total' => 0.001],
    );

    // Assert
    $body = getEventBody($history, index: 2);
    expect($history)->toHaveCount(3)
        ->and(getEventType($history, index: 2))->toBe('generation-create')
        ->and($body['traceId'])->toBe($trace->id)
        ->and($body['parentObservationId'])->toBe($span->id)
        ->and($body['usageDetails'])->toBe(['input' => 5, 'output' => 10])
        ->and($body['costDetails'])->toBe(['total' => 0.001]);
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

    /** @var array{batch: list<array{id: string, timestamp: string, type: string, body: array<string, mixed>}>} $payload */
    $event = $payload['batch'][0];
    expect($payload)->toHaveKey('batch')
        ->and($payload['batch'])->toHaveCount(1)
        ->and($event)->toHaveKey('id')
        ->and($event)->toHaveKey('timestamp')
        ->and($event)->toHaveKey('type')
        ->and($event)->toHaveKey('body')
        ->and($event['type'])->toBe('trace-create');
});

it('generates valid uuid v4 format', function (): void {
    expect(Ingestion::uuid())
        ->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/');
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
    expect($history)->toHaveCount(7)
        ->and(getEventType($history, index: 0))->toBe('trace-create')
        ->and(getEventType($history, index: 1))->toBe('span-create')
        ->and(getEventType($history, index: 2))->toBe('span-create')
        ->and(getEventType($history, index: 3))->toBe('span-update')
        ->and(getEventType($history, index: 4))->toBe('generation-create')
        ->and(getEventType($history, index: 5))->toBe('span-update')
        ->and(getEventType($history, index: 6))->toBe('trace-create');
});
