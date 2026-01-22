<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Testing\Responses;

use GuzzleHttp\Psr7\Response;

class GetSessionListResponse extends Response
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
                    'id' => 'session-abc123',
                    'createdAt' => '2025-01-22T10:00:00.000Z',
                    'projectId' => 'proj-abc123',
                    'environment' => 'production',
                    'bookmarked' => false,
                    'public' => false,
                ],
                [
                    'id' => 'session-def456',
                    'createdAt' => '2025-01-22T09:00:00.000Z',
                    'projectId' => 'proj-abc123',
                    'environment' => 'development',
                    'bookmarked' => true,
                    'public' => true,
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
