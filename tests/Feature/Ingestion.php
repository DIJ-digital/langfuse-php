<?php

declare(strict_types=1);

namespace Tests\Feature;

use DIJ\Langfuse\PHP\Enums\EventType;
use DIJ\Langfuse\PHP\Langfuse;
use DIJ\Langfuse\PHP\Transporters\HttpTransporter;
use DIJ\Langfuse\PHP\ValueObjects\IngestionEvent;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class IngestionTest extends TestCase
{
    public function test_batch_ingestion(): void
    {
        $mockResponse = new Response(
            207,
            ['Content-Type' => 'application/json'],
            json_encode([
                'successes' => [
                    ['id' => 'test-123', 'status' => 201],
                ],
                'errors' => [],
            ])
        );

        $mock = new MockHandler([$mockResponse]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $transporter = new HttpTransporter($client);
        $langfuse = new Langfuse($transporter);

        $event = new IngestionEvent(
            id: 'test-123',
            type: EventType::TRACE_CREATE,
            body: [
                'id' => 'trace-123',
                'name' => 'Test Trace',
                'timestamp' => '2024-01-01T00:00:00.000Z',
            ]
        );

        $response = $langfuse->ingestion()->batch([$event]);

        $this->assertInstanceOf(\DIJ\Langfuse\PHP\Responses\IngestionResponse::class, $response);
        $this->assertFalse($response->hasErrors());
        $this->assertEquals(1, $response->getSuccessCount());
        $this->assertEquals(0, $response->getErrorCount());
    }

    public function test_single_ingestion(): void
    {
        $mockResponse = new Response(
            207,
            ['Content-Type' => 'application/json'],
            json_encode([
                'successes' => [
                    ['id' => 'test-456', 'status' => 201],
                ],
                'errors' => [],
            ])
        );

        $mock = new MockHandler([$mockResponse]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $transporter = new HttpTransporter($client);
        $langfuse = new Langfuse($transporter);

        $response = $langfuse->ingestion()->score(
            id: 'test-456',
            body: [
                'id' => 'score-123',
                'traceId' => 'trace-123',
                'name' => 'Test Score',
                'value' => 0.9,
            ]
        );

        $this->assertInstanceOf(\DIJ\Langfuse\PHP\Responses\IngestionResponse::class, $response);
        $this->assertFalse($response->hasErrors());
        $this->assertEquals(1, $response->getSuccessCount());
    }

    public function test_ingestion_with_errors(): void
    {
        $mockResponse = new Response(
            207,
            ['Content-Type' => 'application/json'],
            json_encode([
                'successes' => [
                    ['id' => 'test-789', 'status' => 201],
                ],
                'errors' => [
                    ['id' => 'test-999', 'status' => 400, 'error' => 'Invalid event data'],
                ],
            ])
        );

        $mock = new MockHandler([$mockResponse]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $transporter = new HttpTransporter($client);
        $langfuse = new Langfuse($transporter);

        $event = new IngestionEvent(
            id: 'test-789',
            type: EventType::SPAN_CREATE,
            body: [
                'id' => 'span-123',
                'traceId' => 'trace-123',
                'name' => 'Test Span',
            ]
        );

        $response = $langfuse->ingestion()->batch([$event]);

        $this->assertInstanceOf(\DIJ\Langfuse\PHP\Responses\IngestionResponse::class, $response);
        $this->assertTrue($response->hasErrors());
        $this->assertEquals(1, $response->getSuccessCount());
        $this->assertEquals(1, $response->getErrorCount());
    }

    public function test_batch_ingestion_with_metadata(): void
    {
        $mockResponse = new Response(
            207,
            ['Content-Type' => 'application/json'],
            json_encode([
                'successes' => [
                    ['id' => 'test-123', 'status' => 201],
                    ['id' => 'test-456', 'status' => 201],
                ],
                'errors' => [],
            ])
        );

        $mock = new MockHandler([$mockResponse]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $transporter = new HttpTransporter($client);
        $langfuse = new Langfuse($transporter);

        $event1 = new IngestionEvent(
            id: 'test-123',
            type: EventType::TRACE_CREATE,
            body: [
                'id' => 'trace-123',
                'name' => 'Test Trace 1',
            ]
        );

        $event2 = new IngestionEvent(
            id: 'test-456',
            type: EventType::SPAN_CREATE,
            body: [
                'id' => 'span-123',
                'traceId' => 'trace-123',
                'name' => 'Test Span',
            ]
        );

        $metadata = [
            'source' => 'php-client',
            'version' => '1.0.0',
        ];

        $response = $langfuse->ingestion()->batch([$event1, $event2], $metadata);

        $this->assertInstanceOf(\DIJ\Langfuse\PHP\Responses\IngestionResponse::class, $response);
        $this->assertFalse($response->hasErrors());
        $this->assertEquals(2, $response->getSuccessCount());
        $this->assertEquals(0, $response->getErrorCount());
    }

    public function test_single_ingestion_with_timestamp_and_metadata(): void
    {
        $mockResponse = new Response(
            207,
            ['Content-Type' => 'application/json'],
            json_encode([
                'successes' => [
                    ['id' => 'test-789', 'status' => 201],
                ],
                'errors' => [],
            ])
        );

        $mock = new MockHandler([$mockResponse]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $transporter = new HttpTransporter($client);
        $langfuse = new Langfuse($transporter);

        $response = $langfuse->ingestion()->generation(
            id: 'test-789',
            body: [
                'id' => 'generation-123',
                'traceId' => 'trace-123',
                'name' => 'Test Generation',
                'model' => 'gpt-4',
                'input' => 'Hello world',
                'output' => 'Hello! How can I help you?',
            ],
            timestamp: '2024-01-01T12:00:00.000Z',
            metadata: [
                'environment' => 'production',
                'user_id' => 'user-123',
            ]
        );

        $this->assertInstanceOf(\DIJ\Langfuse\PHP\Responses\IngestionResponse::class, $response);
        $this->assertFalse($response->hasErrors());
        $this->assertEquals(1, $response->getSuccessCount());
    }

    public function test_ingestion_event_to_array(): void
    {
        $event = new IngestionEvent(
            id: 'test-event',
            type: EventType::OBSERVATION_CREATE,
            body: [
                'id' => 'observation-123',
                'traceId' => 'trace-123',
                'name' => 'Test Observation',
                'value' => 42,
            ],
            timestamp: '2024-01-01T12:00:00.000Z',
            metadata: [
                'source' => 'test',
                'version' => '1.0',
            ]
        );

        $array = $event->toArray();

        $this->assertEquals('test-event', $array['id']);
        $this->assertEquals('observation-create', $array['type']);
        $this->assertEquals('2024-01-01T12:00:00.000Z', $array['timestamp']);
        $this->assertEquals(['source' => 'test', 'version' => '1.0'], $array['metadata']);
        $this->assertEquals([
            'id' => 'observation-123',
            'traceId' => 'trace-123',
            'name' => 'Test Observation',
            'value' => 42,
        ], $array['body']);
    }

    public function test_fluent_ingestion_api(): void
    {
        $mockResponse = new Response(
            207,
            ['Content-Type' => 'application/json'],
            json_encode([
                'successes' => [
                    ['id' => 'test-trace', 'status' => 201],
                    ['id' => 'test-span', 'status' => 201],
                    ['id' => 'test-generation', 'status' => 201],
                ],
                'errors' => [],
            ])
        );

        $mock = new MockHandler([$mockResponse]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $transporter = new HttpTransporter($client);
        $langfuse = new Langfuse($transporter);

        // Test trace creation
        $traceResponse = $langfuse->ingestion()->trace(
            id: 'test-trace',
            body: [
                'id' => 'trace-123',
                'name' => 'Test Trace',
            ]
        );

        $this->assertInstanceOf(\DIJ\Langfuse\PHP\Responses\IngestionResponse::class, $traceResponse);
        $this->assertFalse($traceResponse->hasErrors());

        // Test span creation
        $spanResponse = $langfuse->ingestion()->span(
            id: 'test-span',
            body: [
                'id' => 'span-123',
                'traceId' => 'trace-123',
                'name' => 'Test Span',
            ]
        );

        $this->assertInstanceOf(\DIJ\Langfuse\PHP\Responses\IngestionResponse::class, $spanResponse);
        $this->assertFalse($spanResponse->hasErrors());

        // Test generation creation
        $generationResponse = $langfuse->ingestion()->generation(
            id: 'test-generation',
            body: [
                'id' => 'generation-123',
                'traceId' => 'trace-123',
                'name' => 'Test Generation',
                'model' => 'gpt-4',
                'input' => 'Hello world',
                'output' => 'Hello! How can I help you?',
            ]
        );

        $this->assertInstanceOf(\DIJ\Langfuse\PHP\Responses\IngestionResponse::class, $generationResponse);
        $this->assertFalse($generationResponse->hasErrors());
    }
}
