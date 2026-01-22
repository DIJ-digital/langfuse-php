<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Testing\Responses;

use GuzzleHttp\Psr7\Response;

class GetModelResponse extends Response
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
        ], $data);
    }
}
