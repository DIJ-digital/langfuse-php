<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP;

use DIJ\Langfuse\PHP\Contracts\TransporterInterface;
use DIJ\Langfuse\PHP\Responses\ScoreConfigListResponse;
use DIJ\Langfuse\PHP\Responses\ScoreConfigResponse;
use JsonException;

class ScoreConfig
{
    public function __construct(private readonly TransporterInterface $transporter) {}

    /**
     * @throws JsonException
     */
    public function get(string $configId): ScoreConfigResponse
    {
        $response = $this->transporter->get(
            uri: sprintf('/api/public/score-configs/%s', urlencode($configId)),
        );

        /** @var array{
         *     id: string,
         *     name: string,
         *     dataType: string,
         *     isArchived: bool,
         *     minValue: float|null,
         *     maxValue: float|null,
         *     categories: array<int, array{value: float, label: string}>|null,
         *     description: string|null,
         *     projectId: string,
         *     createdAt: string,
         *     updatedAt: string
         * } $data
         */
        $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        return ScoreConfigResponse::fromArray($data);
    }

    /**
     * @throws JsonException
     */
    public function list(
        ?int $page = null,
        ?int $limit = null,
    ): ScoreConfigListResponse {
        $response = $this->transporter->get(
            uri: '/api/public/score-configs',
            options: ['query' => array_filter([
                'page' => $page,
                'limit' => $limit,
            ])]
        );

        /** @var array{
         *     data: array<int, array{
         *         id: string,
         *         name: string,
         *         dataType: string,
         *         isArchived: bool,
         *         minValue: float|null,
         *         maxValue: float|null,
         *         categories: array<int, array{value: float, label: string}>|null,
         *         description: string|null,
         *         projectId: string,
         *         createdAt: string,
         *         updatedAt: string
         *     }>,
         *     meta: array{page: int, limit: int, totalPages: int, totalItems: int}
         * } $data
         */
        $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        return ScoreConfigListResponse::fromArray($data);
    }

    /**
     * @param  array<int, array{value: float, label: string}>|null  $categories
     *
     * @throws JsonException
     */
    public function create(
        string $name,
        string $dataType,
        ?float $minValue = null,
        ?float $maxValue = null,
        ?array $categories = null,
        ?string $description = null,
    ): ScoreConfigResponse {
        $response = $this->transporter->postJson(
            uri: '/api/public/score-configs',
            data: array_filter([
                'name' => $name,
                'dataType' => $dataType,
                'minValue' => $minValue,
                'maxValue' => $maxValue,
                'categories' => $categories,
                'description' => $description,
            ], fn ($v) => $v !== null),
        );

        /** @var array{
         *     id: string,
         *     name: string,
         *     dataType: string,
         *     isArchived: bool,
         *     minValue: float|null,
         *     maxValue: float|null,
         *     categories: array<int, array{value: float, label: string}>|null,
         *     description: string|null,
         *     projectId: string,
         *     createdAt: string,
         *     updatedAt: string
         * } $data
         */
        $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        return ScoreConfigResponse::fromArray($data);
    }
}
