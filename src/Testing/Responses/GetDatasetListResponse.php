<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Testing\Responses;

use GuzzleHttp\Psr7\Response;

class GetDatasetListResponse extends Response
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
                    'id' => 'dataset-123',
                    'name' => 'test-dataset',
                    'description' => 'A test dataset',
                    'projectId' => 'project-456',
                    'createdAt' => '2025-01-15T10:00:00.000Z',
                    'updatedAt' => '2025-01-15T10:00:00.000Z',
                    'metadata' => ['key' => 'value'],
                    'inputSchema' => null,
                    'expectedOutputSchema' => null,
                ],
                [
                    'id' => 'dataset-456',
                    'name' => 'another-dataset',
                    'description' => 'Another test dataset',
                    'projectId' => 'project-456',
                    'createdAt' => '2025-01-16T10:00:00.000Z',
                    'updatedAt' => '2025-01-16T10:00:00.000Z',
                    'metadata' => null,
                    'inputSchema' => null,
                    'expectedOutputSchema' => null,
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
