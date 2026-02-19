<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Testing\Responses;

use GuzzleHttp\Psr7\Response;

class GetPromptListPageOneResponse extends Response
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
            ],
            'meta' => [
                'page' => 1,
                'limit' => 2,
                'totalPages' => 2,
                'totalItems' => 3,
            ],
            'pagination' => [
                'page' => 1,
                'limit' => 2,
                'totalPages' => 2,
                'totalItems' => 3,
            ],
        ];
    }
}
