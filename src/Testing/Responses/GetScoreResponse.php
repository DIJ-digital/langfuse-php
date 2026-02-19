<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Testing\Responses;

use GuzzleHttp\Psr7\Response;

class GetScoreResponse extends Response
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
        ], $data);
    }
}
