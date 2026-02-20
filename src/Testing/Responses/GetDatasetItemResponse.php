<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Testing\Responses;

use GuzzleHttp\Psr7\Response;

class GetDatasetItemResponse extends Response
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
            'id' => 'item-123',
            'status' => 'ACTIVE',
            'datasetId' => 'dataset-456',
            'datasetName' => 'test-dataset',
            'createdAt' => '2025-01-15T10:00:00.000Z',
            'updatedAt' => '2025-01-15T10:00:00.000Z',
            'input' => ['prompt' => 'What is AI?'],
            'expectedOutput' => ['response' => 'AI is artificial intelligence'],
            'metadata' => ['key' => 'value'],
            'sourceTraceId' => null,
            'sourceObservationId' => null,
        ], $data);
    }
}
