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
 * @param  array<int, array{request: RequestInterface, response: ResponseInterface, options: array<string, mixed>}>  $history
 */
function makeIngestion(array &$history, int $responseCount = 1): Ingestion
{
    $mock = new MockHandler(array_fill(0, $responseCount, new Response(200)));
    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($history)); // @phpstan-ignore parameterByRef.type
    $client = new Client(['base_uri' => 'https://example.test', 'handler' => $stack]);

    return new Ingestion(
        transporter: new HttpTransporter($client),
        environment: 'default',
    );
}

/**
 * Get the OTLP payload from the first flush request.
 *
 * @param  array<int, array{request: RequestInterface, response: ResponseInterface, options: array<string, mixed>}>  $history
 * @return array<string, mixed>
 */
function getOtlpPayload(array $history, int $index = 0): array
{
    /** @var array<string, mixed> $body */
    $body = json_decode((string) $history[$index]['request']->getBody(), true, 512, JSON_THROW_ON_ERROR);

    return $body;
}

/**
 * Get the spans array from an OTLP payload.
 *
 * @param  array<string, mixed>  $payload
 * @return list<array{traceId: string, spanId: string, name: string, kind: int, startTimeUnixNano: string, endTimeUnixNano: string, attributes: array<int, array{key: string, value: array<string, mixed>}>, status: object, parentSpanId?: string}>
 */
function getOtlpSpans(array $payload): array
{
    /** @var array{resourceSpans: array<int, array{scopeSpans: array<int, array{spans: list<array{traceId: string, spanId: string, name: string, kind: int, startTimeUnixNano: string, endTimeUnixNano: string, attributes: array<int, array{key: string, value: array<string, mixed>}>, status: object, parentSpanId?: string}>}>}>} $payload */
    return $payload['resourceSpans'][0]['scopeSpans'][0]['spans'];
}

/**
 * Find a specific OTLP attribute value from a span's attributes array.
 *
 * @param  array<int, array{key: string, value: array<string, mixed>}>  $attributes
 */
function findAttr(array $attributes, string $key): mixed
{
    foreach ($attributes as $attr) {
        if ($attr['key'] === $key) {
            return $attr['value']['stringValue']
                ?? $attr['value']['intValue']
                ?? $attr['value']['boolValue']
                ?? $attr['value']['doubleValue']
                ?? $attr['value']['arrayValue']
                ?? null;
        }
    }

    return null;
}

/**
 * Find the first span matching a predicate.
 *
 * @param  list<array{traceId: string, spanId: string, name: string, kind: int, startTimeUnixNano: string, endTimeUnixNano: string, attributes: array<int, array{key: string, value: array<string, mixed>}>, status: object, parentSpanId?: string}>  $spans
 * @param  callable(array<string, mixed>): bool  $predicate
 * @return array{traceId: string, spanId: string, name: string, kind: int, startTimeUnixNano: string, endTimeUnixNano: string, attributes: array<int, array{key: string, value: array<string, mixed>}>, status: object, parentSpanId?: string}|null
 */
function findSpan(array $spans, callable $predicate): ?array
{
    foreach ($spans as $span) {
        if ($predicate($span)) {
            return $span;
        }
    }

    return null;
}

// ─── Buffering ─────────────────────────────────────────────────────────────

it('buffers spans without sending HTTP until flush', function (): void {
    /** @var array<int, array{request: RequestInterface, response: ResponseInterface, options: array<string, mixed>}> $history */
    $history = [];
    $ingestion = makeIngestion($history);

    $trace = $ingestion->trace(name: 'my-trace');
    $trace->span(name: 'child-span');

    expect($history)->toHaveCount(0)
        ->and($ingestion->getSpans())->toHaveCount(2);

    $ingestion->flush();

    expect($history)->toHaveCount(1)
        ->and($ingestion->getSpans())->toHaveCount(0);
});

// ─── Trace ──────────────────────────────────────────────────────────────────

it('creates a trace and returns a Trace', function (): void {
    /** @var array<int, array{request: RequestInterface, response: ResponseInterface, options: array<string, mixed>}> $history */
    $history = [];
    $ingestion = makeIngestion($history);

    $trace = $ingestion->trace(
        name: 'test-trace',
        traceId: 'a1b2c3d4e5f6a7b8a1b2c3d4e5f6a7b8',
        input: 'hello',
    );

    expect($trace)->toBeInstanceOf(Trace::class)
        ->and($trace->id)->toBe('a1b2c3d4e5f6a7b8a1b2c3d4e5f6a7b8');

    $ingestion->flush();

    $payload = getOtlpPayload($history);
    $spans = getOtlpSpans($payload);

    expect($spans)->toHaveCount(1);

    $rootSpan = $spans[0];

    expect($rootSpan['traceId'])->toBe('a1b2c3d4e5f6a7b8a1b2c3d4e5f6a7b8')
        ->and($rootSpan['name'])->toBe('test-trace')
        ->and($rootSpan)->not->toHaveKey('parentSpanId')
        ->and(findAttr($rootSpan['attributes'], 'langfuse.trace.name'))->toBe('test-trace')
        ->and(findAttr($rootSpan['attributes'], 'langfuse.trace.input'))->toBe('hello')
        ->and(findAttr($rootSpan['attributes'], 'langfuse.environment'))->toBe('default');
});

it('creates a trace with userId and sessionId', function (): void {
    /** @var array<int, array{request: RequestInterface, response: ResponseInterface, options: array<string, mixed>}> $history */
    $history = [];
    $ingestion = makeIngestion($history);

    $ingestion->trace(name: 'test-trace', userId: 'user-123', sessionId: 'sess-456');
    $ingestion->flush();

    $spans = getOtlpSpans(getOtlpPayload($history));

    expect(findAttr($spans[0]['attributes'], 'user.id'))->toBe('user-123')
        ->and(findAttr($spans[0]['attributes'], 'session.id'))->toBe('sess-456');
});

it('omits userId when not provided', function (): void {
    /** @var array<int, array{request: RequestInterface, response: ResponseInterface, options: array<string, mixed>}> $history */
    $history = [];
    $ingestion = makeIngestion($history);

    $ingestion->trace(name: 'test-trace');
    $ingestion->flush();

    $spans = getOtlpSpans(getOtlpPayload($history));

    expect(findAttr($spans[0]['attributes'], 'user.id'))->toBeNull();
});

// ─── Trace update ───────────────────────────────────────────────────────────

it('can update a trace in memory before flush', function (): void {
    /** @var array<int, array{request: RequestInterface, response: ResponseInterface, options: array<string, mixed>}> $history */
    $history = [];
    $ingestion = makeIngestion($history);

    $trace = $ingestion->trace(name: 'my-trace', input: 'start');
    $trace->update(output: 'final result', userId: 'user-456');

    $ingestion->flush();

    expect($history)->toHaveCount(1);

    $spans = getOtlpSpans(getOtlpPayload($history));

    expect(findAttr($spans[0]['attributes'], 'langfuse.trace.output'))->toBe('final result')
        ->and(findAttr($spans[0]['attributes'], 'user.id'))->toBe('user-456')
        ->and(findAttr($spans[0]['attributes'], 'langfuse.trace.input'))->toBe('start');
});

it('trace update returns self for chaining', function (): void {
    /** @var array<int, array{request: RequestInterface, response: ResponseInterface, options: array<string, mixed>}> $history */
    $history = [];
    $ingestion = makeIngestion($history);

    $trace = $ingestion->trace(name: 'chained-trace');

    $result = $trace->update(output: 'step-1')->update(output: 'step-2');

    expect($result)->toBe($trace);

    $ingestion->flush();

    $spans = getOtlpSpans(getOtlpPayload($history));

    // Last update wins
    expect(findAttr($spans[0]['attributes'], 'langfuse.trace.output'))->toBe('step-2');
});

// ─── Generation ─────────────────────────────────────────────────────────────

it('creates a generation with full payload', function (): void {
    /** @var array<int, array{request: RequestInterface, response: ResponseInterface, options: array<string, mixed>}> $history */
    $history = [];
    $ingestion = makeIngestion($history);

    $gen = $ingestion->generation(
        input: ['messages' => [['role' => 'user', 'content' => 'Hi']]],
        output: 'Hello',
        traceId: 'a1b2c3d4e5f6a7b8a1b2c3d4e5f6a7b8',
        name: 'test-generation',
        promptName: 'prompt-x',
        promptVersion: 3,
        model: 'prism',
        modelParameters: ['temperature' => 0.2],
        metadata: ['source' => 'test'],
    );

    expect($gen)->toBeInstanceOf(Generation::class);

    $ingestion->flush();

    $spans = getOtlpSpans(getOtlpPayload($history));

    expect($spans)->toHaveCount(1);

    $span = $spans[0];

    expect($span['traceId'])->toBe('a1b2c3d4e5f6a7b8a1b2c3d4e5f6a7b8')
        ->and($span['name'])->toBe('test-generation')
        ->and(findAttr($span['attributes'], 'langfuse.observation.type'))->toBe('generation')
        ->and(findAttr($span['attributes'], 'langfuse.observation.output'))->toBe('Hello')
        ->and(findAttr($span['attributes'], 'langfuse.observation.model.name'))->toBe('prism')
        ->and(findAttr($span['attributes'], 'langfuse.observation.prompt.name'))->toBe('prompt-x')
        ->and(findAttr($span['attributes'], 'langfuse.observation.prompt.version'))->toBe('3');
});

it('creates a generation with parentObservationId', function (): void {
    /** @var array<int, array{request: RequestInterface, response: ResponseInterface, options: array<string, mixed>}> $history */
    $history = [];
    $ingestion = makeIngestion($history);

    $ingestion->generation(
        input: 'prompt',
        output: 'response',
        traceId: 'a1b2c3d4e5f6a7b8a1b2c3d4e5f6a7b8',
        name: 'gen',
        parentObservationId: 'span-abc',
    );

    $ingestion->flush();

    $spans = getOtlpSpans(getOtlpPayload($history));

    expect($spans[0]['parentSpanId'])->toBe('span-abc'); // @phpstan-ignore offsetAccess.notFound
});

it('generation update mutates in memory', function (): void {
    /** @var array<int, array{request: RequestInterface, response: ResponseInterface, options: array<string, mixed>}> $history */
    $history = [];
    $ingestion = makeIngestion($history);

    $gen = $ingestion->generation(
        input: 'prompt',
        output: 'initial',
        traceId: 'a1b2c3d4e5f6a7b8a1b2c3d4e5f6a7b8',
        name: 'gen',
    );

    $gen->update(output: 'updated response', model: 'gpt-4o');

    $ingestion->flush();

    $spans = getOtlpSpans(getOtlpPayload($history));

    expect(findAttr($spans[0]['attributes'], 'langfuse.observation.output'))->toBe('updated response')
        ->and(findAttr($spans[0]['attributes'], 'langfuse.observation.model.name'))->toBe('gpt-4o');
});

// ─── Span ───────────────────────────────────────────────────────────────────

it('creates a span and returns a Span', function (): void {
    /** @var array<int, array{request: RequestInterface, response: ResponseInterface, options: array<string, mixed>}> $history */
    $history = [];
    $ingestion = makeIngestion($history);

    $span = $ingestion->span(
        traceId: 'a1b2c3d4e5f6a7b8a1b2c3d4e5f6a7b8',
        name: 'web-search-batch',
        input: ['query' => 'test'],
    );

    expect($span)->toBeInstanceOf(Span::class)
        ->and($span->id)->toBeString();

    $ingestion->flush();

    $spans = getOtlpSpans(getOtlpPayload($history));

    expect($spans[0]['traceId'])->toBe('a1b2c3d4e5f6a7b8a1b2c3d4e5f6a7b8')
        ->and($spans[0]['name'])->toBe('web-search-batch')
        ->and(findAttr($spans[0]['attributes'], 'langfuse.observation.type'))->toBe('span');
});

it('creates a span with a provided spanId', function (): void {
    /** @var array<int, array{request: RequestInterface, response: ResponseInterface, options: array<string, mixed>}> $history */
    $history = [];
    $ingestion = makeIngestion($history);

    $span = $ingestion->span(
        traceId: 'a1b2c3d4e5f6a7b8a1b2c3d4e5f6a7b8',
        name: 'my-span',
        spanId: 'custom-span-id',
    );

    expect($span->id)->toBe('custom-span-id');

    $ingestion->flush();

    $spans = getOtlpSpans(getOtlpPayload($history));

    expect($spans[0]['spanId'])->toBe('custom-span-id');
});

it('span update mutates in memory', function (): void {
    /** @var array<int, array{request: RequestInterface, response: ResponseInterface, options: array<string, mixed>}> $history */
    $history = [];
    $ingestion = makeIngestion($history);

    $span = $ingestion->span(
        traceId: 'a1b2c3d4e5f6a7b8a1b2c3d4e5f6a7b8',
        name: 'search-span',
    );

    $result = $span->update(output: ['results' => 3], endTime: '2025-06-01T12:00:00+00:00');

    expect($result)->toBe($span);

    $ingestion->flush();

    $spans = getOtlpSpans(getOtlpPayload($history));

    expect(findAttr($spans[0]['attributes'], 'langfuse.observation.output'))->toBe('{"results":3}')
        ->and($spans[0]['endTimeUnixNano'])->not->toBe($spans[0]['startTimeUnixNano']);
});

// ─── Trace child spawning ───────────────────────────────────────────────────

it('creates a span from a trace with auto-threaded parentSpanId', function (): void {
    /** @var array<int, array{request: RequestInterface, response: ResponseInterface, options: array<string, mixed>}> $history */
    $history = [];
    $ingestion = makeIngestion($history);

    $trace = $ingestion->trace(name: 'my-trace');
    $span = $trace->span(name: 'child-span');

    expect($span)->toBeInstanceOf(Span::class);

    $ingestion->flush();

    $spans = getOtlpSpans(getOtlpPayload($history));

    expect($spans)->toHaveCount(2);

    // Find the child span (the one with parentSpanId)
    $childSpan = findSpan($spans, fn (array $s): bool => isset($s['parentSpanId']));

    expect($childSpan)->not->toBeNull();
    assert($childSpan !== null);
    expect($childSpan['name'])->toBe('child-span')
        ->and($childSpan['traceId'])->toBe($trace->id)
        ->and(findAttr($childSpan['attributes'], 'langfuse.observation.type'))->toBe('span');
});

it('creates a generation from a trace with auto-threaded parentSpanId', function (): void {
    /** @var array<int, array{request: RequestInterface, response: ResponseInterface, options: array<string, mixed>}> $history */
    $history = [];
    $ingestion = makeIngestion($history);

    $trace = $ingestion->trace(name: 'my-trace');
    $gen = $trace->generation(input: 'prompt', output: 'response', name: 'llm-call');

    expect($gen)->toBeInstanceOf(Generation::class);

    $ingestion->flush();

    $spans = getOtlpSpans(getOtlpPayload($history));

    $genSpan = findSpan($spans, fn (array $s): bool => findAttr($s['attributes'], 'langfuse.observation.type') === 'generation'); // @phpstan-ignore argument.type

    expect($genSpan)->not->toBeNull();
    assert($genSpan !== null);
    expect($genSpan['name'])->toBe('llm-call')
        ->and($genSpan['traceId'])->toBe($trace->id)
        ->and($genSpan['parentSpanId'])->toBeString(); // @phpstan-ignore offsetAccess.notFound
});

// ─── Span child spawning ───────────────────────────────────────────────────

it('creates a child span from a span with auto-threaded parentSpanId', function (): void {
    /** @var array<int, array{request: RequestInterface, response: ResponseInterface, options: array<string, mixed>}> $history */
    $history = [];
    $ingestion = makeIngestion($history);

    $trace = $ingestion->trace(name: 'my-trace');
    $parent = $trace->span(name: 'parent-span');
    $child = $parent->span(name: 'child-span');

    $ingestion->flush();

    $spans = getOtlpSpans(getOtlpPayload($history));

    expect($spans)->toHaveCount(3);

    $childSpan = findSpan($spans, fn (array $s): bool => $s['name'] === 'child-span');

    assert($childSpan !== null);
    expect($childSpan['parentSpanId'])->toBe($parent->id) // @phpstan-ignore offsetAccess.notFound
        ->and($childSpan['traceId'])->toBe($trace->id);
});

it('creates a generation from a span with auto-threaded parentSpanId', function (): void {
    /** @var array<int, array{request: RequestInterface, response: ResponseInterface, options: array<string, mixed>}> $history */
    $history = [];
    $ingestion = makeIngestion($history);

    $trace = $ingestion->trace(name: 'my-trace');
    $span = $trace->span(name: 'my-span');
    $gen = $span->generation(input: 'prompt', output: 'response', name: 'llm-call');

    $ingestion->flush();

    $spans = getOtlpSpans(getOtlpPayload($history));

    $genSpan = findSpan($spans, fn (array $s): bool => $s['name'] === 'llm-call');

    assert($genSpan !== null);
    expect($genSpan['parentSpanId'])->toBe($span->id) // @phpstan-ignore offsetAccess.notFound
        ->and($genSpan['traceId'])->toBe($trace->id)
        ->and(findAttr($genSpan['attributes'], 'langfuse.observation.type'))->toBe('generation');
});

// ─── OTLP payload structure ─────────────────────────────────────────────────

it('sends correct OTLP structure on flush', function (): void {
    /** @var array<int, array{request: RequestInterface, response: ResponseInterface, options: array<string, mixed>}> $history */
    $history = [];
    $ingestion = makeIngestion($history);

    $ingestion->trace(name: 'my-trace');
    $ingestion->flush();

    /** @var RequestInterface $request */
    $request = $history[0]['request'];

    expect($request->getMethod())->toBe('POST')
        ->and((string) $request->getUri())->toContain('/api/public/otel/v1/traces')
        ->and($request->getHeaderLine('Content-Type'))->toContain('application/json');

    $payload = getOtlpPayload($history);

    /** @var array{resourceSpans: array<int, array{resource: mixed, scopeSpans: array<int, array{scope: array{name: string}, spans: mixed}>}>} $payload */
    expect($payload)->toHaveKey('resourceSpans')
        ->and($payload['resourceSpans'][0])->toHaveKey('resource')
        ->and($payload['resourceSpans'][0])->toHaveKey('scopeSpans')
        ->and($payload['resourceSpans'][0]['scopeSpans'][0]['scope']['name'])->toBe('langfuse-sdk');
});

it('does not send HTTP when buffer is empty', function (): void {
    /** @var array<int, array{request: RequestInterface, response: ResponseInterface, options: array<string, mixed>}> $history */
    $history = [];
    $ingestion = makeIngestion($history);

    $ingestion->flush();

    expect($history)->toHaveCount(0);
});

// ─── Full example ───────────────────────────────────────────────────────────

it('handles a full trace with spans and generations in one flush', function (): void {
    /** @var array<int, array{request: RequestInterface, response: ResponseInterface, options: array<string, mixed>}> $history */
    $history = [];
    $ingestion = makeIngestion($history);

    $trace = $ingestion->trace(name: 'handle-request', userId: 'user-789', input: 'What is the weather?');

    $span = $trace->span(name: 'search-batch');
    $child = $span->span(name: 'weather-api-call');
    $child->update(output: ['temp' => 22], endTime: '2025-06-01T12:00:00+00:00');

    $gen = $span->generation(input: 'Summarize weather data', output: 'It is 22 degrees.', name: 'summarize', model: 'gpt-4o');

    $span->update(output: ['answer' => 'It is 22 degrees.'], endTime: '2025-06-01T12:00:01+00:00');
    $trace->update(output: 'It is 22 degrees and sunny.');

    // Nothing sent yet
    expect($history)->toHaveCount(0);

    $ingestion->flush();

    // One HTTP call with all spans
    expect($history)->toHaveCount(1);

    $spans = getOtlpSpans(getOtlpPayload($history));

    // 4 spans: trace root + search-batch + weather-api-call + summarize generation
    expect($spans)->toHaveCount(4);

    // Verify trace root
    $rootSpan = findSpan($spans, fn (array $s): bool => ! isset($s['parentSpanId']));
    assert($rootSpan !== null);
    expect(findAttr($rootSpan['attributes'], 'langfuse.trace.output'))->toBe('It is 22 degrees and sunny.')
        ->and(findAttr($rootSpan['attributes'], 'user.id'))->toBe('user-789');

    // Verify generation
    $genSpan = findSpan($spans, fn (array $s): bool => findAttr($s['attributes'], 'langfuse.observation.type') === 'generation'); // @phpstan-ignore argument.type
    assert($genSpan !== null);
    expect($genSpan['name'])->toBe('summarize')
        ->and(findAttr($genSpan['attributes'], 'langfuse.observation.model.name'))->toBe('gpt-4o');
});

// ─── Error handling ─────────────────────────────────────────────────────────

it('throws when flush responds with non-200', function (): void {
    $mock = new MockHandler([new Response(500)]);
    $stack = HandlerStack::create($mock);
    $client = new Client(['base_uri' => 'https://example.test', 'handler' => $stack]);

    $ingestion = new Ingestion(
        transporter: new HttpTransporter($client),
        environment: 'default',
    );

    $ingestion->trace(name: 'err');
    $ingestion->flush();
})->throws(LangfuseException::class);
