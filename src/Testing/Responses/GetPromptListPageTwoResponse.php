<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Testing\Responses;

use GuzzleHttp\Psr7\Response;

class GetPromptListPageTwoResponse extends Response
{
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
                    'name' => 'generate_search_terms',
                    'tags' => [],
                    'lastUpdatedAt' => '2025-05-27T15:46:01.545Z',
                    'versions' => [1, 2],
                    'labels' => ['latest', 'production'],
                    'lastConfig' => [],
                ],
            ],
            'meta' => [
                'page' => 2,
                'limit' => 2,
                'totalPages' => 2,
                'totalItems' => 3,
            ],
            'pagination' => [
                'page' => 2,
                'limit' => 2,
                'totalPages' => 2,
                'totalItems' => 3,
            ],
        ];
    }
}
