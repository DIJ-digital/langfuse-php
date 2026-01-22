<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Testing\Responses;

use GuzzleHttp\Psr7\Response;

class GetDatasetResponse extends Response
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
            'id' => 'dataset-123',
            'name' => 'test-dataset',
            'description' => 'A test dataset',
            'projectId' => 'project-456',
            'createdAt' => '2025-01-15T10:00:00.000Z',
            'updatedAt' => '2025-01-15T10:00:00.000Z',
            'metadata' => ['key' => 'value'],
            'inputSchema' => null,
            'expectedOutputSchema' => null,
        ], $data);
    }
}
