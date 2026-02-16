<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Testing\Responses;

use GuzzleHttp\Psr7\Response;

class GetPromptListResponse extends Response
{
    /**
     * @param array<int, array{name: string, tags: array<int, string>, lastUpdatedAt: string, versions: array<int, int>, labels: array<int, string>, lastConfig: array<string, mixed>}>|null $data
     * @param array<array<string>|string> $headers
     */
    public function __construct(int $status = 200, array $headers = [], string $version = '1.1', ?string $reason = null, ?array $data = null)
    {
        parent::__construct($status, $headers, (string) json_encode($this->payload($data)), $version, $reason);
    }

    /**
     * @param array<int, array{name: string, tags: array<int, string>, lastUpdatedAt: string, versions: array<int, int>, labels: array<int, string>, lastConfig: array<string, mixed>}>|null $data
     * @return array<string, mixed>
     */
    public function payload(?array $data = null): array
    {
        $items = $data ?? [
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
        ];

        $totalItems = count($items);

        return [
            'data' => $items,
            'meta' => [
                'page' => 1,
                'limit' => 50,
                'totalPages' => 1,
                'totalItems' => $totalItems,
            ],
            'pagination' => [
                'page' => 1,
                'limit' => 50,
                'totalPages' => 1,
                'totalItems' => $totalItems,
            ],
        ];
    }
}
