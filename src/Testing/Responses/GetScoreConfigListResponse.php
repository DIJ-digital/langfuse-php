<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Testing\Responses;

use GuzzleHttp\Psr7\Response;

class GetScoreConfigListResponse extends Response
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
                ],
                [
                    'id' => 'config-def456',
                    'name' => 'sentiment',
                    'dataType' => 'CATEGORICAL',
                    'isArchived' => false,
                    'minValue' => null,
                    'maxValue' => null,
                    'categories' => [
                        ['value' => 1.0, 'label' => 'positive'],
                        ['value' => 0.0, 'label' => 'neutral'],
                        ['value' => -1.0, 'label' => 'negative'],
                    ],
                    'description' => 'Sentiment classification',
                    'projectId' => 'proj-abc123',
                    'createdAt' => '2025-01-22T09:00:00.000Z',
                    'updatedAt' => '2025-01-22T09:00:00.000Z',
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
