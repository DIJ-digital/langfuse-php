<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Testing\Responses;

use GuzzleHttp\Psr7\Response;

class GetModelListResponse extends Response
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
                    'id' => 'model-abc123',
                    'modelName' => 'gpt-4-turbo',
                    'matchPattern' => '(?i)^gpt-4-turbo$',
                    'startDate' => '2024-01-01T00:00:00.000Z',
                    'unit' => 'TOKENS',
                    'inputPrice' => 0.00001,
                    'outputPrice' => 0.00003,
                    'totalPrice' => null,
                    'tokenizerId' => 'openai',
                    'tokenizerConfig' => null,
                    'isLangfuseManaged' => false,
                    'createdAt' => '2025-01-22T10:00:00.000Z',
                ],
                [
                    'id' => 'model-def456',
                    'modelName' => 'claude-3-opus',
                    'matchPattern' => '(?i)^claude-3-opus$',
                    'startDate' => null,
                    'unit' => 'TOKENS',
                    'inputPrice' => 0.000015,
                    'outputPrice' => 0.000075,
                    'totalPrice' => null,
                    'tokenizerId' => null,
                    'tokenizerConfig' => null,
                    'isLangfuseManaged' => true,
                    'createdAt' => '2025-01-22T09:00:00.000Z',
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
