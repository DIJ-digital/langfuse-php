<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP;

use DIJ\Langfuse\PHP\Contracts\TransporterInterface;
use DIJ\Langfuse\PHP\Responses\ModelListResponse;
use DIJ\Langfuse\PHP\Responses\ModelResponse;
use JsonException;

class Model
{
    public function __construct(private readonly TransporterInterface $transporter) {}

    /**
     * @throws JsonException
     */
    public function get(string $modelId): ModelResponse
    {
        $response = $this->transporter->get(
            uri: sprintf('/api/public/models/%s', urlencode($modelId)),
        );

        /** @var array{
         *     id: string,
         *     modelName: string,
         *     matchPattern: string,
         *     startDate: string|null,
         *     unit: string|null,
         *     inputPrice: float|null,
         *     outputPrice: float|null,
         *     totalPrice: float|null,
         *     tokenizerId: string|null,
         *     tokenizerConfig: mixed,
         *     isLangfuseManaged: bool,
         *     createdAt: string
         * } $data
         */
        $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        return ModelResponse::fromArray($data);
    }

    /**
     * @throws JsonException
     */
    public function list(
        ?int $page = null,
        ?int $limit = null,
    ): ModelListResponse {
        $response = $this->transporter->get(
            uri: '/api/public/models',
            options: ['query' => array_filter([
                'page' => $page,
                'limit' => $limit,
            ])]
        );

        /** @var array{
         *     data: array<int, array{
         *         id: string,
         *         modelName: string,
         *         matchPattern: string,
         *         startDate: string|null,
         *         unit: string|null,
         *         inputPrice: float|null,
         *         outputPrice: float|null,
         *         totalPrice: float|null,
         *         tokenizerId: string|null,
         *         tokenizerConfig: mixed,
         *         isLangfuseManaged: bool,
         *         createdAt: string
         *     }>,
         *     meta: array{page: int, limit: int, totalPages: int, totalItems: int}
         * } $data
         */
        $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        return ModelListResponse::fromArray($data);
    }

    /**
     * @throws JsonException
     */
    public function create(
        string $modelName,
        string $matchPattern,
        ?string $startDate = null,
        ?string $unit = null,
        ?float $inputPrice = null,
        ?float $outputPrice = null,
        ?float $totalPrice = null,
        ?string $tokenizerId = null,
        mixed $tokenizerConfig = null,
    ): ModelResponse {
        $response = $this->transporter->postJson(
            uri: '/api/public/models',
            data: array_filter([
                'modelName' => $modelName,
                'matchPattern' => $matchPattern,
                'startDate' => $startDate,
                'unit' => $unit,
                'inputPrice' => $inputPrice,
                'outputPrice' => $outputPrice,
                'totalPrice' => $totalPrice,
                'tokenizerId' => $tokenizerId,
                'tokenizerConfig' => $tokenizerConfig,
            ], fn ($v) => $v !== null),
        );

        /** @var array{
         *     id: string,
         *     modelName: string,
         *     matchPattern: string,
         *     startDate: string|null,
         *     unit: string|null,
         *     inputPrice: float|null,
         *     outputPrice: float|null,
         *     totalPrice: float|null,
         *     tokenizerId: string|null,
         *     tokenizerConfig: mixed,
         *     isLangfuseManaged: bool,
         *     createdAt: string
         * } $data
         */
        $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        return ModelResponse::fromArray($data);
    }

    public function delete(string $modelId): void
    {
        $this->transporter->delete(
            uri: sprintf('/api/public/models/%s', urlencode($modelId)),
        );
    }
}
