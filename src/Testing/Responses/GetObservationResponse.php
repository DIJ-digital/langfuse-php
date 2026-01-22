<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Testing\Responses;

use GuzzleHttp\Psr7\Response;

class GetObservationResponse extends Response
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
            'id' => 'obs-abc123',
            'traceId' => 'trace-abc123',
            'type' => 'GENERATION',
            'name' => 'llm-generation',
            'startTime' => '2025-01-22T10:30:00.000Z',
            'endTime' => '2025-01-22T10:30:02.500Z',
            'completionStartTime' => '2025-01-22T10:30:00.500Z',
            'model' => 'gpt-4o',
            'modelParameters' => ['temperature' => 0.7, 'max_tokens' => 1000],
            'input' => ['messages' => [['role' => 'user', 'content' => 'Hello']]],
            'output' => ['content' => 'Hi there! How can I help you?'],
            'metadata' => ['key' => 'value'],
            'version' => 'v1',
            'parentObservationId' => null,
            'level' => 'DEFAULT',
            'statusMessage' => null,
            'promptId' => 'prompt-123',
            'promptName' => 'greeting-prompt',
            'promptVersion' => 1,
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
        ], $data);
    }
}
