<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Testing\Responses;

use GuzzleHttp\Psr7\Response;

class GetTraceResponse extends Response
{
    /**
     * @param  array<array<string>|string>  $headers
     * @param  array<string, mixed>  $data
     */
    public function __construct(int $status = 200, array $headers = [], string $version = '1.1', ?string $reason = null, array $data = [])
    {
        parent::__construct($status, $headers, (string) json_encode($this->payload($data)), $version, $reason);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function payload(array $data = []): array
    {
        return array_merge([
            'id' => 'trace-abc123',
            'timestamp' => '2025-01-22T10:30:00.000Z',
            'name' => 'test-trace',
            'input' => ['prompt' => 'Hello, world!'],
            'output' => ['response' => 'Hi there!'],
            'sessionId' => 'session-xyz789',
            'release' => '1.0.0',
            'version' => 'v1',
            'userId' => 'user-123',
            'metadata' => ['key' => 'value'],
            'tags' => ['production', 'test'],
            'public' => false,
            'projectId' => 'proj-abc123',
            'createdAt' => '2025-01-22T10:30:00.000Z',
            'updatedAt' => '2025-01-22T10:30:00.000Z',
            'externalId' => null,
            'totalCost' => 0.0025,
            'latency' => 1.5,
            'observations' => [
                [
                    'id' => 'obs-001',
                    'name' => 'generation-1',
                    'type' => 'GENERATION',
                ],
            ],
            'scores' => [
                [
                    'id' => 'score-001',
                    'name' => 'quality',
                    'value' => 0.95,
                ],
            ],
            'htmlPath' => '/traces/trace-abc123',
            'environment' => 'production',
        ], $data);
    }
}
