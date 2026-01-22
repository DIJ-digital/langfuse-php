<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Testing\Responses;

use GuzzleHttp\Psr7\Response;

class GetObservationListResponse extends Response
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
                    'id' => 'obs-abc123',
                    'traceId' => 'trace-abc123',
                    'type' => 'GENERATION',
                    'name' => 'llm-generation-1',
                    'startTime' => '2025-01-22T10:30:00.000Z',
                    'endTime' => '2025-01-22T10:30:02.500Z',
                    'completionStartTime' => '2025-01-22T10:30:00.500Z',
                    'model' => 'gpt-4o',
                    'modelParameters' => ['temperature' => 0.7],
                    'input' => ['prompt' => 'Hello'],
                    'output' => ['response' => 'Hi there!'],
                    'metadata' => null,
                    'version' => 'v1',
                    'parentObservationId' => null,
                    'level' => 'DEFAULT',
                    'statusMessage' => null,
                    'promptId' => null,
                    'promptName' => null,
                    'promptVersion' => null,
                    'usage' => [
                        'input' => 10,
                        'output' => 25,
                        'total' => 35,
                        'unit' => 'TOKENS',
                        'inputCost' => 0.0001,
                        'outputCost' => 0.0005,
                        'totalCost' => 0.0006,
                    ],
                    'calculatedInputCost' => 0.0001,
                    'calculatedOutputCost' => 0.0005,
                    'calculatedTotalCost' => 0.0006,
                    'latency' => 2.5,
                    'timeToFirstToken' => 0.5,
                    'projectId' => 'proj-abc123',
                    'createdAt' => '2025-01-22T10:30:00.000Z',
                    'updatedAt' => '2025-01-22T10:30:02.500Z',
                    'environment' => 'production',
                ],
                [
                    'id' => 'obs-def456',
                    'traceId' => 'trace-abc123',
                    'type' => 'SPAN',
                    'name' => 'retrieval-span',
                    'startTime' => '2025-01-22T10:29:58.000Z',
                    'endTime' => '2025-01-22T10:29:59.500Z',
                    'completionStartTime' => null,
                    'model' => null,
                    'modelParameters' => null,
                    'input' => ['query' => 'search term'],
                    'output' => ['results' => [['id' => 1], ['id' => 2]]],
                    'metadata' => ['source' => 'vector-db'],
                    'version' => 'v1',
                    'parentObservationId' => null,
                    'level' => 'DEFAULT',
                    'statusMessage' => null,
                    'promptId' => null,
                    'promptName' => null,
                    'promptVersion' => null,
                    'usage' => null,
                    'calculatedInputCost' => null,
                    'calculatedOutputCost' => null,
                    'calculatedTotalCost' => null,
                    'latency' => 1.5,
                    'timeToFirstToken' => null,
                    'projectId' => 'proj-abc123',
                    'createdAt' => '2025-01-22T10:29:58.000Z',
                    'updatedAt' => '2025-01-22T10:29:59.500Z',
                    'environment' => 'production',
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
