<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP;

use DIJ\Langfuse\PHP\Contracts\TransporterInterface;
use DIJ\Langfuse\PHP\Responses\DatasetRunItemListResponse;
use DIJ\Langfuse\PHP\Responses\DatasetRunItemResponse;
use JsonException;

class DatasetRunItem
{
    public function __construct(private readonly TransporterInterface $transporter) {}

    /**
     * @param  array<string, mixed>|null  $metadata
     *
     * @throws JsonException
     */
    public function create(
        string $runName,
        string $datasetItemId,
        ?string $traceId = null,
        ?string $observationId = null,
        ?string $runDescription = null,
        ?array $metadata = null,
    ): DatasetRunItemResponse {
        $response = $this->transporter->postJson(
            '/api/public/dataset-run-items',
            array_filter([
                'runName' => $runName,
                'datasetItemId' => $datasetItemId,
                'traceId' => $traceId,
                'observationId' => $observationId,
                'runDescription' => $runDescription,
                'metadata' => $metadata,
            ], fn ($value) => $value !== null)
        );

        /** @var array{id: string, datasetRunId: string, datasetRunName: string, datasetItemId: string, traceId: string, createdAt: string, updatedAt: string, observationId?: string|null} $data */
        $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        return DatasetRunItemResponse::fromArray($data);
    }

    /**
     * @throws JsonException
     */
    public function list(
        string $datasetId,
        string $runName,
        ?int $page = null,
        ?int $limit = null,
    ): DatasetRunItemListResponse {
        $response = $this->transporter->get(
            '/api/public/dataset-run-items',
            ['query' => array_filter([
                'datasetId' => $datasetId,
                'runName' => $runName,
                'page' => $page,
                'limit' => $limit,
            ], fn ($value) => $value !== null)]
        );

        /** @var array{data: array<int, array{id: string, datasetRunId: string, datasetRunName: string, datasetItemId: string, traceId: string, createdAt: string, updatedAt: string, observationId?: string|null}>, meta: array{page: int, limit: int, totalPages: int, totalItems: int}} $data */
        $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        return DatasetRunItemListResponse::fromArray($data);
    }
}
