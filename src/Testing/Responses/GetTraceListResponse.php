<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Testing\Responses;

use GuzzleHttp\Psr7\Response;

class GetTraceListResponse extends Response
{
    /**
     * @param  array<array<string>|string>  $headers
     */
    public function __construct(int $status = 200, array $headers = [], string $version = '1.1', ?string $reason = null)
    {
        parent::__construct($status, $headers, (string) json_encode($this->payload()), $version, $reason);
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'data' => [
                [
                    'id' => 'trace-abc123',
                    'timestamp' => '2025-01-22T10:30:00.000Z',
                    'name' => 'test-trace-1',
                    'input' => ['prompt' => 'Hello, world!'],
                    'output' => ['response' => 'Hi there!'],
                    'sessionId' => 'session-xyz789',
                    'release' => '1.0.0',
                    'version' => 'v1',
                    'userId' => 'user-123',
                    'metadata' => ['key' => 'value'],
                    'tags' => ['production'],
                    'public' => false,
                    'projectId' => 'proj-abc123',
                    'createdAt' => '2025-01-22T10:30:00.000Z',
                    'updatedAt' => '2025-01-22T10:30:00.000Z',
                    'externalId' => null,
                    'totalCost' => 0.0025,
                    'latency' => 1.5,
                    'htmlPath' => '/traces/trace-abc123',
                    'environment' => 'production',
                ],
                [
                    'id' => 'trace-def456',
                    'timestamp' => '2025-01-22T09:15:00.000Z',
                    'name' => 'test-trace-2',
                    'input' => ['prompt' => 'What is the weather?'],
                    'output' => ['response' => 'It is sunny.'],
                    'sessionId' => 'session-abc123',
                    'release' => '1.0.0',
                    'version' => 'v1',
                    'userId' => 'user-456',
                    'metadata' => null,
                    'tags' => ['development'],
                    'public' => true,
                    'projectId' => 'proj-abc123',
                    'createdAt' => '2025-01-22T09:15:00.000Z',
                    'updatedAt' => '2025-01-22T09:15:00.000Z',
                    'externalId' => 'ext-001',
                    'totalCost' => 0.0012,
                    'latency' => 0.8,
                    'htmlPath' => '/traces/trace-def456',
                    'environment' => 'development',
                ],
            ],
            'meta' => [
                'page' => 1,
                'limit' => 10,
                'totalPages' => 1,
                'totalItems' => 2,
            ],
        ];
    }
}
