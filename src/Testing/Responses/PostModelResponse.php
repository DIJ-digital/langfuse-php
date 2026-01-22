<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Testing\Responses;

use GuzzleHttp\Psr7\Response;

class PostModelResponse extends Response
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
            'id' => 'model-new123',
            'modelName' => 'custom-model',
            'matchPattern' => '(?i)^custom-model$',
            'startDate' => null,
            'unit' => 'TOKENS',
            'inputPrice' => 0.00002,
            'outputPrice' => 0.00004,
            'totalPrice' => null,
            'tokenizerId' => null,
            'tokenizerConfig' => null,
            'isLangfuseManaged' => false,
            'createdAt' => '2025-01-22T11:00:00.000Z',
        ], $data);
    }
}
