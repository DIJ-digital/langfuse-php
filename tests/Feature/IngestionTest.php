<?php

declare(strict_types=1);

use DIJ\Langfuse\PHP\Exceptions\LangfuseException;
use DIJ\Langfuse\PHP\Ingestion;
use DIJ\Langfuse\PHP\Transporters\HttpTransporter;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

it('posts trace via HttpTransporter and shapes payload', function (): void {
    // Arrange
    /** @var array<int, array{request: RequestInterface, response: ResponseInterface, options: array<string, mixed>}> $history */
    $history = [];

    $mock = new MockHandler([
        new Response(207),
    ]);

    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($history));

    $client = new Client([
        'base_uri' => 'https://example.test',
        'handler' => $stack,
    ]);

    $ingestion = new Ingestion(new HttpTransporter($client), environment: 'default');

    // Act
    $ingestion->trace(
        input: 'hello',
        output: null,
        traceId: 'trace-1',
        name: 'test-trace',
    );

    // Assert
    expect($history)->toHaveCount(1);
    /** @var array{request: RequestInterface, response: ResponseInterface, options: array<string, mixed>} $first */
    $first = $history[0];
    /** @var RequestInterface $request */
    $request = $first['request'];

    expect($request->getMethod())->toBe('POST');
    expect((string) $request->getUri())->toContain('/api/public/ingestion');
    expect($request->getHeaderLine('Content-Type'))->toContain('application/json');

    /** @var array{batch: array<int, array{type: string, body: array<string, mixed>}>} $body */
    $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);

    expect($body['batch'])->toBeArray()->and(count($body['batch']))->toBe(1);
    /** @var array{type: string, body: array<string, mixed>} $envelope */
    $envelope = $body['batch'][0];
    expect($envelope['type'])->toBe('trace-create');
    expect($envelope['body']['id'])->toBe('trace-1');
    expect($envelope['body']['name'])->toBe('test-trace');
    expect($envelope['body']['environment'])->toBe('default');
    expect($envelope['body']['input'])->toBe('hello');
    expect($envelope['body'])->not->toHaveKey('output');
    expect($envelope['body'])->not->toHaveKey('sessionId');
    expect($envelope['body'])->not->toHaveKey('metadata');
});

it('posts generation via HttpTransporter with full payload', function (): void {
    // Arrange
    /** @var array<int, array{request: RequestInterface, response: ResponseInterface, options: array<string, mixed>}> $history */
    $history = [];

    $mock = new MockHandler([
        new Response(200),
    ]);

    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($history));

    $client = new Client([
        'base_uri' => 'https://example.test',
        'handler' => $stack,
    ]);

    $ingestion = new Ingestion(new HttpTransporter($client), environment: 'default');

    // Act
    $ingestion->generation(
        input: ['messages' => [['role' => 'user', 'content' => 'Hi']]],
        output: 'Hello',
        traceId: 'trace-2',
        name: 'test-generation',
        sessionId: 'sess-1',
        promptName: 'prompt-x',
        promptVersion: 3,
        model: 'prism',
        modelParameters: ['temperature' => 0.2],
        metadata: ['source' => 'test'],
    );

    // Assert
    expect($history)->toHaveCount(1);
    /** @var array{request: RequestInterface, response: ResponseInterface, options: array<string, mixed>} $first */
    $first = $history[0];
    /** @var RequestInterface $request */
    $request = $first['request'];

    expect($request->getMethod())->toBe('POST');
    expect((string) $request->getUri())->toContain('/api/public/ingestion');
    expect($request->getHeaderLine('Content-Type'))->toContain('application/json');

    /** @var array{batch: array<int, array{type: string, body: array<string, mixed>}>} $body */
    $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
    /** @var array{type: string, body: array<string, mixed>} $envelope */
    $envelope = $body['batch'][0];

    expect($envelope['type'])->toBe('generation-create');
    expect($envelope['body']['traceId'])->toBe('trace-2');
    expect($envelope['body']['name'])->toBe('test-generation');
    expect($envelope['body']['sessionId'])->toBe('sess-1');
    expect($envelope['body']['promptName'])->toBe('prompt-x');
    expect($envelope['body']['promptVersion'])->toBe(3);
    expect($envelope['body']['model'])->toBe('prism');
    expect($envelope['body']['modelParameters'])->toBe(['temperature' => 0.2]);
    expect($envelope['body']['metadata'])->toBe(['source' => 'test']);
});

it('throws when ingestion responds with non-200/207', function (): void {
    // Arrange
    $history = [];

    $mock = new MockHandler([
        new Response(500),
    ]);

    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($history));

    $client = new Client([
        'base_uri' => 'https://example.test',
        'handler' => $stack,
    ]);

    $ingestion = new Ingestion(new HttpTransporter($client), environment: 'default');

    // Act / Assert
    $ingestion->trace(
        input: null,
        output: null,
        traceId: 'trace-err',
        name: 'err',
    );
})->throws(LangfuseException::class);
