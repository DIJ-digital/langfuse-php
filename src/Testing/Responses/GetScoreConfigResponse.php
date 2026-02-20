<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Testing\Responses;

use GuzzleHttp\Psr7\Response;

class GetScoreConfigResponse extends Response
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
            'id' => 'config-abc123',
            'name' => 'quality-score',
            'dataType' => 'NUMERIC',
            'isArchived' => false,
            'minValue' => 0.0,
            'maxValue' => 1.0,
            'categories' => null,
            'description' => 'Quality score for LLM outputs',
            'projectId' => 'proj-abc123',
            'createdAt' => '2025-01-22T10:00:00.000Z',
            'updatedAt' => '2025-01-22T10:00:00.000Z',
        ], $data);
    }
}
