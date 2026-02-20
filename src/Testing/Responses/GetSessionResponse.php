<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Testing\Responses;

use GuzzleHttp\Psr7\Response;

class GetSessionResponse extends Response
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
            'id' => 'session-abc123',
            'createdAt' => '2025-01-22T10:00:00.000Z',
            'projectId' => 'proj-abc123',
            'environment' => 'production',
            'bookmarked' => false,
            'public' => false,
            'traces' => [
                [
                    'id' => 'trace-001',
                    'name' => 'trace-in-session',
                    'timestamp' => '2025-01-22T10:30:00.000Z',
                ],
                [
                    'id' => 'trace-002',
                    'name' => 'another-trace',
                    'timestamp' => '2025-01-22T10:35:00.000Z',
                ],
            ],
        ], $data);
    }
}
