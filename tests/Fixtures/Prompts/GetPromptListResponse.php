<?php

declare(strict_types=1);

namespace Tests\Fixtures\Prompts;

use GuzzleHttp\Psr7\Response;

class GetPromptListResponse extends Response
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
                    'name' => 'general_instructions',
                    'tags' => [],
                    'lastUpdatedAt' => '2025-05-28T06:48:35.156Z',
                    'versions' => [1],
                    'labels' => ['latest', 'production'],
                    'lastConfig' => [],
                ],
                [
                    'name' => 'generate_basic_report_input',
                    'tags' => [],
                    'lastUpdatedAt' => '2025-05-28T06:59:30.405Z',
                    'versions' => [1],
                    'labels' => ['latest', 'production'],
                    'lastConfig' => [],
                ],
                [
                    'name' => 'generate_search_terms',
                    'tags' => [],
                    'lastUpdatedAt' => '2025-05-27T15:46:01.545Z',
                    'versions' => [1, 2],
                    'labels' => ['latest', 'production'],
                    'lastConfig' => [],
                ],
                [
                    'name' => 'validate_search_results',
                    'tags' => [],
                    'lastUpdatedAt' => '2025-05-28T06:57:09.533Z',
                    'versions' => [1],
                    'labels' => ['latest', 'production'],
                    'lastConfig' => [],
                ],
                [
                    'name' => 'web_search',
                    'tags' => [],
                    'lastUpdatedAt' => '2025-05-28T06:54:13.449Z',
                    'versions' => [1],
                    'labels' => ['latest', 'production'],
                    'lastConfig' => [],
                ],
            ],
            'meta' => [
                'page' => 1,
                'limit' => 10,
                'totalPages' => 1,
                'totalItems' => 5,
            ],
            'pagination' => [
                'page' => 1,
                'limit' => 10,
                'totalPages' => 1,
                'totalItems' => 5,
            ],
        ];
    }
}
