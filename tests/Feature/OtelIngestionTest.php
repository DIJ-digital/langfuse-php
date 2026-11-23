<?php

declare(strict_types=1);

use DIJ\Langfuse\PHP\Enums\ObservationLevel;
use DIJ\Langfuse\PHP\Exceptions\LangfuseException;
use DIJ\Langfuse\PHP\Langfuse;
use DIJ\Langfuse\PHP\OtelIngestion;
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
 * @param array<int, array{request: RequestInterface, response: ResponseInterface, options: array<string, mixed>}> $history
 */
function makeOtelIngestion(array &$history, int $responseCount = 10, string $environment = 'testing', string $version = '', string $serviceName = 'unknown_service:php'): OtelIngestion
{
    $mock = new MockHandler(array_fill(0, $responseCount, new Response(200)));
    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($history)); // @phpstan-ignore parameterByRef.type
    $client = new Client(['base_uri' => 'https://example.test', 'handler' => $stack]);

    return new OtelIngestion(
        transporter: new HttpTransporter($client),
        environment: $environment,
        version: $version,
        serviceName: $serviceName,
    );
}

/**
 * Get the OTLP payload from a captured request.
 *
 * @param array<int, array{request: RequestInterface, response: ResponseInterface, options: array<string, mixed>}> $history
 * @return array{resourceSpans: list<array{resource: array{attributes: list<array{key: string, value: array<string, mixed>}>}, scopeSpans: list<array{scope: array{name: string}, spans: list<array<string, mixed>>}>}>}
 */
function getOtelPayload(array $history, int $index = 0): array
{
    /** @var array{resourceSpans: list<array{resource: array{attributes: list<array{key: string, value: array<string, mixed>}>}, scopeSpans: list<array{scope: array{name: string}, spans: list<array<string, mixed>>}>}>} $body */
    $body = json_decode((string) $history[$index]['request']->getBody(), true, 512, JSON_THROW_ON_ERROR);

    return $body;
}

/**
 * Get the first span from a captured OTLP request.
 *
 * @param array<int, array{request: RequestInterface, response: ResponseInterface, options: array<string, mixed>}> $history
 * @return array<string, mixed>
 */
function getOtelSpan(array $history, int $index = 0): array
{
    $payload = getOtelPayload($history, $index);

    return $payload['resourceSpans'][0]['scopeSpans'][0]['spans'][0];
}

/**
 * Find an attribute value from a span's attributes array.
 *
 * @param array<string, mixed> $span
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

it('exports nothing until an observation ends', function (): void {
    $history = [];
    $ingestion = makeOtelIngestion($history);

    $trace = $ingestion->trace(name: 'run');
    $trace->span(name: 'step')->generation(name: 'llm');

    expect($history)->toBeEmpty();
});

it('exports one OTLP/HTTP JSON request per ended observation', function (): void {
    $history = [];
    $ingestion = makeOtelIngestion($history, serviceName: 'my-app');

    $ingestion->trace(name: 'run')->end();

    $request = $history[0]['request'];
    $payload = getOtelPayload($history);

    expect($request->getMethod())->toBe('POST')
        ->and((string) $request->getUri())->toBe('https://example.test/api/public/otel/v1/traces')
        ->and($request->getHeaderLine('Content-Type'))->toBe('application/json')
        ->and($request->getHeaderLine('x-langfuse-ingestion-version'))->toBe('4')
        ->and($payload['resourceSpans'][0]['resource']['attributes'])->toBe([['key' => 'service.name', 'value' => ['stringValue' => 'my-app']]])
        ->and($payload['resourceSpans'][0]['scopeSpans'][0]['scope'])->toBe(['name' => 'langfuse-php'])
        ->and($payload['resourceSpans'][0]['scopeSpans'][0]['spans'])->toHaveCount(1);
});

it('exports a trace as a root span with trace attributes', function (): void {
    $history = [];
    $ingestion = makeOtelIngestion($history, version: '1.2.3');

    $trace = $ingestion->trace(
        name: 'run',
        sessionId: 'session-1',
        userId: 'user-1',
        input: ['question' => 'weather?'],
        output: 'sunny',
        metadata: ['source' => 'test', 'count' => 2],
        tags: ['a', 'b'],
        public: true,
        release: 'v1',
    );
    $trace->end();

    $span = getOtelSpan($history);

    expect($span['traceId'])->toBe($trace->traceId)
        ->and($span['spanId'])->toBe($trace->spanId)
        ->and($span)->not->toHaveKey('parentSpanId')
        ->and($span['name'])->toBe('run')
        ->and($span['kind'])->toBe(1)
        ->and($span)->not->toHaveKey('status')
        ->and(findAttribute($span, 'langfuse.trace.name'))->toBe(['stringValue' => 'run'])
        ->and(findAttribute($span, 'langfuse.session.id'))->toBe(['stringValue' => 'session-1'])
        ->and(findAttribute($span, 'langfuse.user.id'))->toBe(['stringValue' => 'user-1'])
        ->and(findAttribute($span, 'langfuse.observation.type'))->toBe(['stringValue' => 'span'])
        ->and(findAttribute($span, 'langfuse.observation.input'))->toBe(['stringValue' => '{"question":"weather?"}'])
        ->and(findAttribute($span, 'langfuse.trace.input'))->toBe(['stringValue' => '{"question":"weather?"}'])
        ->and(findAttribute($span, 'langfuse.observation.output'))->toBe(['stringValue' => 'sunny'])
        ->and(findAttribute($span, 'langfuse.trace.metadata.source'))->toBe(['stringValue' => 'test'])
        ->and(findAttribute($span, 'langfuse.trace.metadata.count'))->toBe(['intValue' => '2'])
        ->and(findAttribute($span, 'langfuse.trace.tags'))->toBe(['arrayValue' => ['values' => [['stringValue' => 'a'], ['stringValue' => 'b']]]])
        ->and(findAttribute($span, 'langfuse.trace.public'))->toBe(['boolValue' => true])
        ->and(findAttribute($span, 'langfuse.release'))->toBe(['stringValue' => 'v1'])
        ->and(findAttribute($span, 'langfuse.environment'))->toBe(['stringValue' => 'testing'])
        ->and(findAttribute($span, 'langfuse.version'))->toBe(['stringValue' => '1.2.3']);
});

it('propagates trace attributes to child observations', function (): void {
    $history = [];
    $ingestion = makeOtelIngestion($history);

    $trace = $ingestion->trace(name: 'run', sessionId: 'session-1', input: 'only on the root');
    $span = $trace->span(name: 'step');
    $span->end();

    $child = getOtelSpan($history);

    expect($child['parentSpanId'])->toBe($trace->spanId)
        ->and($child['traceId'])->toBe($trace->traceId)
        ->and(findAttribute($child, 'langfuse.session.id'))->toBe(['stringValue' => 'session-1'])
        ->and(findAttribute($child, 'langfuse.trace.name'))->toBe(['stringValue' => 'run'])
        ->and(findAttribute($child, 'langfuse.environment'))->toBe(['stringValue' => 'testing'])
        ->and(findAttribute($child, 'langfuse.trace.input'))->toBeNull()
        ->and(findAttribute($child, 'langfuse.observation.type'))->toBe(['stringValue' => 'span']);
});

it('exports a generation with model, usage and prompt attributes', function (): void {
    $history = [];
    $ingestion = makeOtelIngestion($history);

    $span = $ingestion->trace(name: 'run')->span(name: 'step');
    $generation = $span->generation(
        name: 'llm',
        input: ['messages' => [['role' => 'user', 'content' => 'Hi']]],
        output: 'Hello',
        model: 'gpt-4o',
        modelParameters: ['temperature' => 0.2],
        usageDetails: ['input' => 10, 'output' => 5],
        promptName: 'prompt-x',
        promptVersion: 3,
        metadata: ['finish_reason' => 'stop'],
    );
    $generation->end();

    $otelSpan = getOtelSpan($history);

    expect($otelSpan['parentSpanId'])->toBe($span->spanId)
        ->and(findAttribute($otelSpan, 'langfuse.observation.type'))->toBe(['stringValue' => 'generation'])
        ->and(findAttribute($otelSpan, 'langfuse.observation.input'))->toBe(['stringValue' => '{"messages":[{"role":"user","content":"Hi"}]}'])
        ->and(findAttribute($otelSpan, 'langfuse.observation.output'))->toBe(['stringValue' => 'Hello'])
        ->and(findAttribute($otelSpan, 'langfuse.observation.model.name'))->toBe(['stringValue' => 'gpt-4o'])
        ->and(findAttribute($otelSpan, 'langfuse.observation.model.parameters'))->toBe(['stringValue' => '{"temperature":0.2}'])
        ->and(findAttribute($otelSpan, 'langfuse.observation.usage_details'))->toBe(['stringValue' => '{"input":10,"output":5}'])
        ->and(findAttribute($otelSpan, 'langfuse.observation.prompt.name'))->toBe(['stringValue' => 'prompt-x'])
        ->and(findAttribute($otelSpan, 'langfuse.observation.prompt.version'))->toBe(['intValue' => '3'])
        ->and(findAttribute($otelSpan, 'langfuse.observation.metadata.finish_reason'))->toBe(['stringValue' => 'stop']);
});

it('applies updates made before the observation ends', function (): void {
    $history = [];
    $ingestion = makeOtelIngestion($history);

    $span = $ingestion->trace(name: 'run')->span(name: 'step', input: 'in');
    $span->update(name: 'renamed', output: ['results' => 3]);
    $span->end();

    $otelSpan = getOtelSpan($history);

    expect($otelSpan['name'])->toBe('renamed')
        ->and(findAttribute($otelSpan, 'langfuse.observation.input'))->toBe(['stringValue' => 'in'])
        ->and(findAttribute($otelSpan, 'langfuse.observation.output'))->toBe(['stringValue' => '{"results":3}']);
});

it('exports an observation only once', function (): void {
    $history = [];
    $ingestion = makeOtelIngestion($history);

    $span = $ingestion->trace(name: 'run')->span(name: 'step');
    $span->end();
    $span->end();

    expect($history)->toHaveCount(1);
});

it('marks an observation with level ERROR as an OTel error status', function (): void {
    $history = [];
    $ingestion = makeOtelIngestion($history);

    $span = $ingestion->trace(name: 'run')->span(name: 'step');
    $span->update(level: ObservationLevel::ERROR, statusMessage: 'Timeout');
    $span->end();

    $otelSpan = getOtelSpan($history);

    expect($otelSpan['status'])->toBe(['code' => 2, 'message' => 'Timeout'])
        ->and(findAttribute($otelSpan, 'langfuse.observation.level'))->toBe(['stringValue' => 'ERROR'])
        ->and(findAttribute($otelSpan, 'langfuse.observation.status_message'))->toBe(['stringValue' => 'Timeout']);
});

it('leaves the status unset for non-error levels', function (): void {
    $history = [];
    $ingestion = makeOtelIngestion($history);

    $span = $ingestion->trace(name: 'run')->span(name: 'step');
    $span->update(level: ObservationLevel::WARNING, statusMessage: 'Slow');
    $span->end();

    expect(getOtelSpan($history))->not->toHaveKey('status');
});

it('uses the given start and end times in nanoseconds', function (): void {
    $history = [];
    $ingestion = makeOtelIngestion($history);

    $ingestion->trace(name: 'run', startTime: '2025-01-01T00:00:00.250+00:00')->end('2025-01-01T00:00:05+00:00');

    $span = getOtelSpan($history);

    expect($span['startTimeUnixNano'])->toBe('1735689600250000000')
        ->and($span['endTimeUnixNano'])->toBe('1735689605000000000');
});

it('defaults start and end times to now', function (): void {
    $history = [];
    $ingestion = makeOtelIngestion($history);

    $before = OtelIngestion::nowNano();
    $ingestion->trace(name: 'run')->end();
    $after = OtelIngestion::nowNano();

    $span = getOtelSpan($history);

    expect($span['startTimeUnixNano'])->toMatch('/^\d{19}$/')
        ->and($span['startTimeUnixNano'] >= $before)->toBeTrue()
        ->and($span['endTimeUnixNano'] <= $after)->toBeTrue()
        ->and($span['endTimeUnixNano'] >= $span['startTimeUnixNano'])->toBeTrue();
});

it('continues a trace from another process with derived ids', function (): void {
    $history = [];
    $ingestion = makeOtelIngestion($history);

    $traceId = OtelIngestion::traceId('run-1');
    $rootSpanId = OtelIngestion::spanId('run-1');

    $ingestion->trace(name: 'run', traceId: $traceId, spanId: $rootSpanId)->end();
    $ingestion->span(traceId: $traceId, parentSpanId: $rootSpanId, name: 'step', spanId: OtelIngestion::spanId('step-1'))->end();
    $ingestion->generation(traceId: $traceId, parentSpanId: OtelIngestion::spanId('step-1'), name: 'llm', model: 'gpt-4o')->end();

    expect(getOtelSpan($history, 0)['traceId'])->toBe(md5('run-1'))
        ->and(getOtelSpan($history, 0)['spanId'])->toBe(substr(md5('run-1'), 0, 16))
        ->and(getOtelSpan($history, 1)['parentSpanId'])->toBe($rootSpanId)
        ->and(getOtelSpan($history, 2)['parentSpanId'])->toBe(OtelIngestion::spanId('step-1'))
        ->and(findAttribute(getOtelSpan($history, 2), 'langfuse.observation.type'))->toBe(['stringValue' => 'generation']);
});

it('generates valid random ids', function (): void {
    expect(OtelIngestion::traceId())->toMatch('/^[0-9a-f]{32}$/')
        ->and(OtelIngestion::spanId())->toMatch('/^[0-9a-f]{16}$/')
        ->and(OtelIngestion::traceId())->not->toBe(OtelIngestion::traceId());
});

it('rejects ids that are not valid OTel ids', function (string $traceId, string $spanId): void {
    $history = [];

    makeOtelIngestion($history)->trace(name: 'run', traceId: $traceId, spanId: $spanId);
})->throws(InvalidArgumentException::class)->with([
    'uuid trace id' => ['5f0e6a5e-2b8c-4b7f-9d0e-1a2b3c4d5e6f', 'aabbccdd11223344'],
    'zero trace id' => [str_repeat('0', 32), 'aabbccdd11223344'],
    'uppercase span id' => [str_repeat('a', 32), 'AABBCCDD11223344'],
    'short span id' => [str_repeat('a', 32), 'aabb'],
]);

it('replaces invalid UTF-8 instead of failing', function (): void {
    $history = [];
    $ingestion = makeOtelIngestion($history);

    $ingestion->trace(name: 'run', input: ['text' => "bad \xB1 byte"], output: "bad \xB1 byte")->end();

    $span = getOtelSpan($history);

    expect(findAttribute($span, 'langfuse.observation.input'))->toBe(['stringValue' => '{"text":"bad \\ufffd byte"}'])
        ->and(findAttribute($span, 'langfuse.observation.output'))->toBe(['stringValue' => "bad \u{FFFD} byte"]);
});

it('throws when the OTel endpoint responds with an error', function (): void {
    $client = new Client(['base_uri' => 'https://example.test', 'handler' => HandlerStack::create(new MockHandler([new Response(500)]))]);

    (new OtelIngestion(new HttpTransporter($client)))->trace(name: 'run')->end();
})->throws(LangfuseException::class);

it('creates OtelIngestion from Langfuse with its environment and version', function (): void {
    $history = [];
    $stack = HandlerStack::create(new MockHandler([new Response(200)]));
    $stack->push(Middleware::history($history));
    $client = new Client(['base_uri' => 'https://example.test', 'handler' => $stack]);

    (new Langfuse(new HttpTransporter($client), environment: 'production', version: '2.0.0'))
        ->otelIngestion(serviceName: 'my-app')
        ->trace(name: 'run')
        ->end();

    /** @var array<int, array{request: RequestInterface, response: ResponseInterface, options: array<string, mixed>}> $history */
    $payload = getOtelPayload($history);
    $span = getOtelSpan($history);

    expect($payload['resourceSpans'][0]['resource']['attributes'][0]['value'])->toBe(['stringValue' => 'my-app'])
        ->and(findAttribute($span, 'langfuse.environment'))->toBe(['stringValue' => 'production'])
        ->and(findAttribute($span, 'langfuse.version'))->toBe(['stringValue' => '2.0.0']);
});
