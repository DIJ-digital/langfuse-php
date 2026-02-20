<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Testing\Responses;

use GuzzleHttp\Psr7\Response;

class PostCommentResponse extends Response
{
    /**
     * @param array<array<string>|string> $headers
     * @param array<string, mixed> $data
     */
    public function __construct(int $status = 200, array $headers = [], string $version = '1.1', ?string $reason = null, array $data = [])
    {
        parent::__construct($status, $headers, (string) json_encode($this->payload($data)), $version, $reason);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function payload(array $data = []): array
    {
        return array_merge([
            'id' => 'comment-new123',
            'content' => 'New comment content',
            'objectType' => 'trace',
            'objectId' => 'trace-123',
            'authorUserId' => 'user-456',
            'createdAt' => '2025-01-22T11:00:00.000Z',
            'updatedAt' => '2025-01-22T11:00:00.000Z',
            'projectId' => 'proj-abc123',
        ], $data);
    }
}
