<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP;

use DIJ\Langfuse\PHP\Contracts\TransporterInterface;
use DIJ\Langfuse\PHP\Enums\DatasetStatus;
use DIJ\Langfuse\PHP\Responses\DatasetItemListResponse;
use DIJ\Langfuse\PHP\Responses\DatasetItemResponse;
use JsonException;

class DatasetItem
{
    public function __construct(private readonly TransporterInterface $transporter) {}

    /**
     * @param  array<string, mixed>|null  $metadata
     *
     * @throws JsonException
     */
    public function create(
        string $datasetName,
        mixed $input = null,
        mixed $expectedOutput = null,
        ?array $metadata = null,
        ?string $sourceTraceId = null,
        ?string $sourceObservationId = null,
        ?string $id = null,
        ?DatasetStatus $status = null,
    ): DatasetItemResponse {
        $response = $this->transporter->postJson(
            '/api/public/dataset-items',
            array_filter([
                'datasetName' => $datasetName,
                'input' => $input,
                'expectedOutput' => $expectedOutput,
                'metadata' => $metadata,
                'sourceTraceId' => $sourceTraceId,
                'sourceObservationId' => $sourceObservationId,
                'id' => $id,
                'status' => $status?->value,
            ], fn ($value) => $value !== null)
        );

        /** @var array{id: string, status: string, datasetId: string, datasetName: string, createdAt: string, updatedAt: string, input?: mixed, expectedOutput?: mixed, metadata?: array<string, mixed>|null, sourceTraceId?: string|null, sourceObservationId?: string|null} $data */
        $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        return DatasetItemResponse::fromArray($data);
    }

    /**
     * @throws JsonException
     */
    public function get(string $id): DatasetItemResponse
    {
        $response = $this->transporter->get(
            sprintf('/api/public/dataset-items/%s', urlencode($id))
        );

        /** @var array{id: string, status: string, datasetId: string, datasetName: string, createdAt: string, updatedAt: string, input?: mixed, expectedOutput?: mixed, metadata?: array<string, mixed>|null, sourceTraceId?: string|null, sourceObservationId?: string|null} $data */
        $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        return DatasetItemResponse::fromArray($data);
    }

    /**
     * @throws JsonException
     */
    public function list(
        ?string $datasetName = null,
        ?string $sourceTraceId = null,
        ?string $sourceObservationId = null,
        ?int $page = null,
        ?int $limit = null,
    ): DatasetItemListResponse {
        $response = $this->transporter->get(
            '/api/public/dataset-items',
            ['query' => array_filter([
                'datasetName' => $datasetName,
                'sourceTraceId' => $sourceTraceId,
                'sourceObservationId' => $sourceObservationId,
                'page' => $page,
                'limit' => $limit,
            ], fn ($value) => $value !== null)]
        );

        /** @var array{data: array<int, array{id: string, status: string, datasetId: string, datasetName: string, createdAt: string, updatedAt: string, input?: mixed, expectedOutput?: mixed, metadata?: array<string, mixed>|null, sourceTraceId?: string|null, sourceObservationId?: string|null}>, meta: array{page: int, limit: int, totalPages: int, totalItems: int}} $data */
        $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        return DatasetItemListResponse::fromArray($data);
    }

    public function delete(string $id): void
    {
        $this->transporter->delete(
            sprintf('/api/public/dataset-items/%s', urlencode($id))
        );
    }
}
