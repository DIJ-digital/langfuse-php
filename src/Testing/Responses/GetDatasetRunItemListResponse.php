<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Testing\Responses;

use GuzzleHttp\Psr7\Response;

class GetDatasetRunItemListResponse extends Response
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
                    'id' => 'run-item-123',
                    'datasetRunId' => 'run-456',
                    'datasetRunName' => 'test-run',
                    'datasetItemId' => 'item-789',
                    'traceId' => 'trace-abc',
                    'createdAt' => '2025-01-15T10:00:00.000Z',
                    'updatedAt' => '2025-01-15T10:00:00.000Z',
                    'observationId' => null,
                ],
                [
                    'id' => 'run-item-456',
                    'datasetRunId' => 'run-456',
                    'datasetRunName' => 'test-run',
                    'datasetItemId' => 'item-012',
                    'traceId' => 'trace-def',
                    'createdAt' => '2025-01-16T10:00:00.000Z',
                    'updatedAt' => '2025-01-16T10:00:00.000Z',
                    'observationId' => 'obs-xyz',
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
