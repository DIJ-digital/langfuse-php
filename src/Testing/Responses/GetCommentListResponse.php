<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Testing\Responses;

use GuzzleHttp\Psr7\Response;

class GetCommentListResponse extends Response
{
    /**
     * @param array<array<string>|string> $headers
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
                    'id' => 'comment-abc123',
                    'content' => 'First comment',
                    'objectType' => 'trace',
                    'objectId' => 'trace-123',
                    'authorUserId' => 'user-456',
                    'createdAt' => '2025-01-22T10:00:00.000Z',
                    'updatedAt' => '2025-01-22T10:00:00.000Z',
                    'projectId' => 'proj-abc123',
                ],
                [
                    'id' => 'comment-def456',
                    'content' => 'Second comment',
                    'objectType' => 'observation',
                    'objectId' => 'obs-789',
                    'authorUserId' => null,
                    'createdAt' => '2025-01-22T09:00:00.000Z',
                    'updatedAt' => '2025-01-22T09:00:00.000Z',
                    'projectId' => 'proj-abc123',
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
