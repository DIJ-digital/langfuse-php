<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Testing\Responses;

use GuzzleHttp\Psr7\Response;

class GetScoreListResponse extends Response
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
            'data' => [
                [
                    'id' => 'score-123',
                    'traceId' => 'trace-456',
                    'name' => 'accuracy',
                    'value' => 0.95,
                    'dataType' => 'NUMERIC',
                    'source' => 'API',
                    'observationId' => null,
                    'comment' => 'Good accuracy',
                    'configId' => null,
                    'queueId' => null,
                    'stringValue' => null,
                    'createdAt' => '2025-01-22T10:00:00.000Z',
                    'updatedAt' => '2025-01-22T10:00:00.000Z',
                    'authorUserId' => null,
                ],
                [
                    'id' => 'score-789',
                    'traceId' => 'trace-456',
                    'name' => 'helpfulness',
                    'value' => 'helpful',
                    'dataType' => 'CATEGORICAL',
                    'source' => 'ANNOTATION',
                    'observationId' => 'obs-123',
                    'comment' => null,
                    'configId' => 'config-abc',
                    'queueId' => null,
                    'stringValue' => 'helpful',
                    'createdAt' => '2025-01-22T11:00:00.000Z',
                    'updatedAt' => '2025-01-22T11:00:00.000Z',
                    'authorUserId' => 'user-xyz',
                ],
            ],
            'meta' => [
                'page' => 1,
                'limit' => 50,
                'totalPages' => 1,
                'totalItems' => 2,
            ],
        ], $data);
    }
}
