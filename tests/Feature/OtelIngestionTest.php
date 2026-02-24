<?php

declare(strict_types=1);

use DIJ\Langfuse\PHP\Exceptions\LangfuseException;
use DIJ\Langfuse\PHP\OtelIngestion;
use DIJ\Langfuse\PHP\OtelIngestion\Generation;
use DIJ\Langfuse\PHP\OtelIngestion\Span;
use DIJ\Langfuse\PHP\OtelIngestion\Trace;
use DIJ\Langfuse\PHP\Transporters\HttpTransporter;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Create an OtelIngestion instance with a mock HTTP transport that captures requests.
 *
 * @param  array<int, array{request: RequestInterface, response: ResponseInterface, options: array<string, mixed>}>  $history
 */
function makeOtelIngestion(array &$history, int $responseCount = 10, string $environment = 'testing', string $version = ''): OtelIngestion
{
    $mock = new MockHandler(array_fill(0, $responseCount, new Response(200)));
    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($history)); // @phpstan-ignore parameterByRef.type
    $client = new Client(['base_uri' => 'https://example.test', 'handler' => $stack]);

    return new OtelIngestion(
        transporter: new HttpTransporter($client),
        environment: $environment,
        version: $version,
    );
}

/**
 * Get the OTLP payload from a captured request.
 *
 * @param  array<int, array{request: RequestInterface, response: ResponseInterface, options: array<string, mixed>}>  $history
 * @return array<string, mixed>
 */
function getOtelPayload(array $history, int $index = 0): array
{
    /** @var array<string, mixed> $body */
    $body = json_decode((string) $history[$index]['request']->getBody(), true, 512, JSON_THROW_ON_ERROR);

    return $body;
}

/**
 * Get the first span from a captured OTLP request.
 *
 * @param  array<int, array{request: RequestInterface, response: ResponseInterface, options: array<string, mixed>}>  $history
 * @return array<string, mixed>
 */
function getOtelSpan(array $history, int $index = 0): array
{
    $payload = getOtelPayload($history, $index);

    /** @var array{resourceSpans: list<array{scopeSpans: list<array{spans: list<array<string, mixed>>}>}>} $payload */
    return $payload['resourceSpans'][0]['scopeSpans'][0]['spans'][0];
}

/**
 * Find an attribute value from a span's attributes array.
 *
 * @param  array<string, mixed>  $span
 * @return array<string, mixed>|null
 */
function findAttribute(array $span, string $key): ?array
{
    /** @var list<array{key: string, value: array<string, mixed>}> $attributes */
    $attributes = $span['attributes'] ?? [];

    foreach ($attributes as $attr) {
        if ($attr['key'] === $key) {
            return $attr['value'];
        }
    }

    return null;
}

// ─── OTLP Payload Structure ────────────────────────────────────────────────

it('sends correct OTLP JSON structure', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeOtelIngestion($history);

    // Act
    $ingestion->trace(name: 'my-trace');

    // Assert
    $payload = getOtelPayload($history);

    expect($payload)->toHaveKey('resourceSpans')
        ->and($payload['resourceSpans'])->toHaveCount(1);

    /** @var array{resourceSpans: list<array{resource: array<string, mixed>, scopeSpans: list<array{scope: array{name: string}, spans: list<mixed>}>}>} $payload */
    $resourceSpan = $payload['resourceSpans'][0];

    expect($resourceSpan)->toHaveKey('resource')
        ->and($resourceSpan)->toHaveKey('scopeSpans')
        ->and($resourceSpan['scopeSpans'])->toHaveCount(1);

    $scopeSpan = $resourceSpan['scopeSpans'][0];
    expect($scopeSpan['scope']['name'])->toBe('langfuse-php')
        ->and($scopeSpan['spans'])->toHaveCount(1);
});

it('posts to the OTel endpoint', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeOtelIngestion($history);

    // Act
    $ingestion->trace(name: 'my-trace');

    // Assert
    /** @var RequestInterface $request */
    $request = $history[0]['request'];
    expect($request->getMethod())->toBe('POST')
        ->and((string) $request->getUri())->toContain('/api/public/otel/v1/traces');
});

// ─── Trace ──────────────────────────────────────────────────────────────────

it('creates a trace as an OTLP root span', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeOtelIngestion($history);

    // Act
    $trace = $ingestion->trace(name: 'test-trace');

    // Assert
    expect($trace)->toBeInstanceOf(Trace::class)
        ->and($history)->toHaveCount(1);

    $span = getOtelSpan($history);
    expect($span['name'])->toBe('test-trace')
        ->and($span)->toHaveKey('traceId')
        ->and($span)->toHaveKey('spanId')
        ->and($span)->not->toHaveKey('parentSpanId')
        ->and($span)->toHaveKey('startTimeUnixNano')
        ->and($span['kind'])->toBe(1);
});

it('creates a trace with a custom traceId', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeOtelIngestion($history);

    // Act
    $trace = $ingestion->trace(name: 'test-trace', traceId: 'aabbccdd11223344aabbccdd11223344');

    // Assert
    expect($trace->id)->toBe('aabbccdd11223344aabbccdd11223344');

    $span = getOtelSpan($history);
    expect($span['traceId'])->toBe('aabbccdd11223344aabbccdd11223344');
});

it('creates a trace with userId and sessionId attributes', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeOtelIngestion($history);

    // Act
    $ingestion->trace(name: 'test-trace', userId: 'user-123', sessionId: 'sess-456');

    // Assert
    $span = getOtelSpan($history);
    expect(findAttribute($span, 'langfuse.user.id'))->toBe(['stringValue' => 'user-123'])
        ->and(findAttribute($span, 'langfuse.session.id'))->toBe(['stringValue' => 'sess-456']);
});

it('creates a trace with input and output attributes', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeOtelIngestion($history);

    // Act
    $ingestion->trace(name: 'test-trace', input: 'hello', output: 'world');

    // Assert
    $span = getOtelSpan($history);
    expect(findAttribute($span, 'langfuse.trace.input'))->toBe(['stringValue' => 'hello'])
        ->and(findAttribute($span, 'langfuse.trace.output'))->toBe(['stringValue' => 'world']);
});

it('creates a trace with array input serialized to JSON', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeOtelIngestion($history);

    // Act
    $ingestion->trace(name: 'test-trace', input: ['query' => 'test']);

    // Assert
    $span = getOtelSpan($history);
    expect(findAttribute($span, 'langfuse.trace.input'))->toBe(['stringValue' => '{"query":"test"}']);
});

it('creates a trace with tags as string array attribute', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeOtelIngestion($history);

    // Act
    $ingestion->trace(name: 'test-trace', tags: ['tag1', 'tag2']);

    // Assert
    $span = getOtelSpan($history);
    $tagsAttr = findAttribute($span, 'langfuse.trace.tags');

    expect($tagsAttr)->toBe([
        'arrayValue' => [
            'values' => [
                ['stringValue' => 'tag1'],
                ['stringValue' => 'tag2'],
            ],
        ],
    ]);
});

it('creates a trace with metadata as individual attributes', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeOtelIngestion($history);

    // Act
    $ingestion->trace(name: 'test-trace', metadata: ['source' => 'test', 'count' => 5]);

    // Assert
    $span = getOtelSpan($history);
    expect(findAttribute($span, 'langfuse.trace.metadata.source'))->toBe(['stringValue' => 'test'])
        ->and(findAttribute($span, 'langfuse.trace.metadata.count'))->toBe(['stringValue' => '5']);
});

it('creates a trace with public and release attributes', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeOtelIngestion($history);

    // Act
    $ingestion->trace(name: 'test-trace', public: true, release: 'v1.2.3');

    // Assert
    $span = getOtelSpan($history);
    expect(findAttribute($span, 'langfuse.trace.public'))->toBe(['boolValue' => true])
        ->and(findAttribute($span, 'langfuse.release'))->toBe(['stringValue' => 'v1.2.3']);
});

it('includes environment and version attributes on trace', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeOtelIngestion($history, environment: 'production', version: '2.0.0');

    // Act
    $ingestion->trace(name: 'test-trace');

    // Assert
    $span = getOtelSpan($history);
    expect(findAttribute($span, 'langfuse.environment'))->toBe(['stringValue' => 'production'])
        ->and(findAttribute($span, 'langfuse.version'))->toBe(['stringValue' => '2.0.0']);
});

it('includes langfuse.trace.name attribute', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeOtelIngestion($history);

    // Act
    $ingestion->trace(name: 'my-named-trace');

    // Assert
    $span = getOtelSpan($history);
    expect(findAttribute($span, 'langfuse.trace.name'))->toBe(['stringValue' => 'my-named-trace']);
});

// ─── Trace update ───────────────────────────────────────────────────────────

it('updates a trace with a second POST', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeOtelIngestion($history);

    // Act
    $trace = $ingestion->trace(name: 'my-trace', input: 'start');
    $result = $trace->update(output: 'final result', userId: 'user-456');

    // Assert
    expect($result)->toBe($trace)
        ->and($history)->toHaveCount(2);

    $span = getOtelSpan($history, index: 1);
    expect(findAttribute($span, 'langfuse.observation.output'))->toBe(['stringValue' => 'final result'])
        ->and(findAttribute($span, 'langfuse.user.id'))->toBe(['stringValue' => 'user-456']);
});

// ─── Span ───────────────────────────────────────────────────────────────────

it('creates a span as a child OTLP span', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeOtelIngestion($history);

    // Act
    $trace = $ingestion->trace(name: 'my-trace');
    $span = $trace->span(name: 'child-span', input: ['query' => 'test']);

    // Assert
    expect($span)->toBeInstanceOf(Span::class)
        ->and($history)->toHaveCount(2);

    $otelSpan = getOtelSpan($history, index: 1);
    expect($otelSpan['name'])->toBe('child-span')
        ->and($otelSpan['traceId'])->toBe($trace->id)
        ->and($otelSpan['parentSpanId'])->toBe($trace->rootSpanId)
        ->and(findAttribute($otelSpan, 'langfuse.observation.type'))->toBe(['stringValue' => 'span'])
        ->and(findAttribute($otelSpan, 'langfuse.observation.input'))->toBe(['stringValue' => '{"query":"test"}']);
});

it('creates a span with a custom spanId', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeOtelIngestion($history);

    // Act
    $trace = $ingestion->trace(name: 'my-trace');
    $span = $trace->span(name: 'my-span', spanId: 'aabbccdd11223344');

    // Assert
    expect($span->id)->toBe('aabbccdd11223344');

    $otelSpan = getOtelSpan($history, index: 1);
    expect($otelSpan['spanId'])->toBe('aabbccdd11223344');
});

it('updates a span with a second POST', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeOtelIngestion($history);

    // Act
    $trace = $ingestion->trace(name: 'my-trace');
    $span = $trace->span(name: 'search-span');
    $result = $span->update(output: ['results' => 3], endTime: '2025-06-01T12:00:00+00:00');

    // Assert
    expect($result)->toBe($span)
        ->and($history)->toHaveCount(3);

    $otelSpan = getOtelSpan($history, index: 2);
    expect(findAttribute($otelSpan, 'langfuse.observation.output'))->toBe(['stringValue' => '{"results":3}'])
        ->and($otelSpan)->toHaveKey('endTimeUnixNano');
});

// ─── Span child spawning ───────────────────────────────────────────────────

it('creates a child span from a span with parentSpanId', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeOtelIngestion($history);

    // Act
    $trace = $ingestion->trace(name: 'my-trace');
    $parent = $trace->span(name: 'parent-span');
    $child = $parent->span(name: 'child-span');

    // Assert
    expect($history)->toHaveCount(3);

    $otelSpan = getOtelSpan($history, index: 2);
    expect($otelSpan['traceId'])->toBe($trace->id)
        ->and($otelSpan['parentSpanId'])->toBe($parent->id)
        ->and($otelSpan['name'])->toBe('child-span');
});

// ─── Generation ─────────────────────────────────────────────────────────────

it('creates a generation with full payload', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeOtelIngestion($history);

    // Act
    $trace = $ingestion->trace(name: 'my-trace');
    $gen = $trace->generation(
        name: 'test-generation',
        input: ['messages' => [['role' => 'user', 'content' => 'Hi']]],
        output: 'Hello',
        promptName: 'prompt-x',
        promptVersion: 3,
        model: 'gpt-4o',
        modelParameters: ['temperature' => 0.2],
        metadata: ['source' => 'test'],
    );

    // Assert
    expect($gen)->toBeInstanceOf(Generation::class)
        ->and($history)->toHaveCount(2);

    $otelSpan = getOtelSpan($history, index: 1);
    expect($otelSpan['traceId'])->toBe($trace->id)
        ->and($otelSpan['parentSpanId'])->toBe($trace->rootSpanId)
        ->and($otelSpan['name'])->toBe('test-generation')
        ->and(findAttribute($otelSpan, 'langfuse.observation.type'))->toBe(['stringValue' => 'generation'])
        ->and(findAttribute($otelSpan, 'langfuse.observation.input'))->toBe(['stringValue' => '{"messages":[{"role":"user","content":"Hi"}]}'])
        ->and(findAttribute($otelSpan, 'langfuse.observation.output'))->toBe(['stringValue' => 'Hello'])
        ->and(findAttribute($otelSpan, 'langfuse.observation.model.name'))->toBe(['stringValue' => 'gpt-4o'])
        ->and(findAttribute($otelSpan, 'langfuse.observation.model.parameters'))->toBe(['stringValue' => '{"temperature":0.2}'])
        ->and(findAttribute($otelSpan, 'langfuse.observation.prompt.name'))->toBe(['stringValue' => 'prompt-x'])
        ->and(findAttribute($otelSpan, 'langfuse.observation.prompt.version'))->toBe(['intValue' => '3'])
        ->and(findAttribute($otelSpan, 'langfuse.observation.metadata.source'))->toBe(['stringValue' => 'test']);
});

it('creates a generation from a span', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeOtelIngestion($history);

    // Act
    $trace = $ingestion->trace(name: 'my-trace');
    $span = $trace->span(name: 'my-span');
    $gen = $span->generation(name: 'llm-call', input: 'prompt', output: 'response', model: 'gpt-4o');

    // Assert
    expect($history)->toHaveCount(3);

    $otelSpan = getOtelSpan($history, index: 2);
    expect($otelSpan['traceId'])->toBe($trace->id)
        ->and($otelSpan['parentSpanId'])->toBe($span->id)
        ->and(findAttribute($otelSpan, 'langfuse.observation.type'))->toBe(['stringValue' => 'generation'])
        ->and(findAttribute($otelSpan, 'langfuse.observation.model.name'))->toBe(['stringValue' => 'gpt-4o']);
});

it('updates a generation with a second POST', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeOtelIngestion($history);

    // Act
    $trace = $ingestion->trace(name: 'my-trace');
    $gen = $trace->generation(name: 'gen', input: 'prompt', output: 'initial');
    $gen->update(output: 'updated response', model: 'gpt-4o');

    // Assert
    expect($history)->toHaveCount(3);

    $otelSpan = getOtelSpan($history, index: 2);
    expect(findAttribute($otelSpan, 'langfuse.observation.output'))->toBe(['stringValue' => 'updated response'])
        ->and(findAttribute($otelSpan, 'langfuse.observation.model.name'))->toBe(['stringValue' => 'gpt-4o'])
        ->and(findAttribute($otelSpan, 'langfuse.observation.type'))->toBe(['stringValue' => 'generation']);
});

// ─── ID formats ─────────────────────────────────────────────────────────────

it('generates valid 128-bit trace IDs (32 hex chars)', function (): void {
    $traceId = OtelIngestion::traceId();

    expect($traceId)->toMatch('/^[0-9a-f]{32}$/');
});

it('generates valid 64-bit span IDs (16 hex chars)', function (): void {
    $spanId = OtelIngestion::spanId();

    expect($spanId)->toMatch('/^[0-9a-f]{16}$/');
});

it('generates nanosecond timestamps', function (): void {
    $nano = OtelIngestion::nowNano();

    // Should be a numeric string of 19-20 digits (nanoseconds since epoch)
    expect($nano)->toMatch('/^\d{19,20}$/');
});

it('converts ISO timestamps to nanoseconds', function (): void {
    $nano = OtelIngestion::isoToNano('2025-01-01T00:00:00+00:00');

    // 2025-01-01T00:00:00Z = 1735689600 seconds
    expect($nano)->toBe('1735689600000000000');
});

// ─── Error handling ─────────────────────────────────────────────────────────

it('throws when OTel endpoint responds with error', function (): void {
    // Arrange
    $mock = new MockHandler([new Response(500)]);
    $stack = HandlerStack::create($mock);
    $client = new Client(['base_uri' => 'https://example.test', 'handler' => $stack]);

    $ingestion = new OtelIngestion(
        transporter: new HttpTransporter($client),
        environment: 'testing',
    );

    // Act & Assert
    $ingestion->trace(name: 'err');
})->throws(LangfuseException::class);

// ─── Full example ───────────────────────────────────────────────────────────

it('handles a full trace with spans and generations via OTel', function (): void {
    // Arrange
    $history = [];
    $ingestion = makeOtelIngestion($history, responseCount: 20);

    // Act
    $trace = $ingestion->trace(name: 'handle-request', userId: 'user-789', input: 'What is the weather?');
    $span = $trace->span(name: 'search-batch');
    $child = $span->span(name: 'weather-api-call');
    $child->update(output: ['temp' => 22], endTime: '2025-06-01T12:00:00+00:00');
    $gen = $span->generation(name: 'summarize', input: 'Summarize weather data', output: 'It is 22 degrees.', model: 'gpt-4o');
    $span->update(output: ['answer' => 'It is 22 degrees.'], endTime: '2025-06-01T12:00:01+00:00');
    $trace->update(output: 'It is 22 degrees and sunny.');

    // Assert: 7 HTTP calls
    expect($history)->toHaveCount(7);

    // Verify trace span has no parentSpanId
    $rootSpan = getOtelSpan($history, index: 0);
    expect($rootSpan)->not->toHaveKey('parentSpanId')
        ->and($rootSpan['name'])->toBe('handle-request');

    // Verify all child spans share the same traceId
    for ($i = 1; $i < 7; $i++) {
        $s = getOtelSpan($history, index: $i);
        expect($s['traceId'])->toBe($trace->id);
    }

    // Verify generation has correct observation type
    $genSpan = getOtelSpan($history, index: 4);
    expect(findAttribute($genSpan, 'langfuse.observation.type'))->toBe(['stringValue' => 'generation'])
        ->and(findAttribute($genSpan, 'langfuse.observation.model.name'))->toBe(['stringValue' => 'gpt-4o']);
});

// ─── Langfuse integration ──────────────────────────────────────────────────

it('creates OtelIngestion from Langfuse', function (): void {
    // Arrange
    $mock = new MockHandler([new Response(200)]);
    $stack = HandlerStack::create($mock);
    $client = new Client(['base_uri' => 'https://example.test', 'handler' => $stack]);

    $langfuse = new \DIJ\Langfuse\PHP\Langfuse(
        transporter: new HttpTransporter($client),
        environment: 'production',
        version: '1.0.0',
    );

    // Act
    $otel = $langfuse->otelIngestion();

    // Assert
    expect($otel)->toBeInstanceOf(OtelIngestion::class);
});
