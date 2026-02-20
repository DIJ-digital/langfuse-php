<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP;

use DIJ\Langfuse\PHP\Contracts\TransporterInterface;
use DIJ\Langfuse\PHP\Responses\DatasetListResponse;
use DIJ\Langfuse\PHP\Responses\DatasetResponse;
use DIJ\Langfuse\PHP\Responses\DatasetRunListResponse;
use JsonException;

class Dataset
{
    public function __construct(private readonly TransporterInterface $transporter) {}

    /**
     * @param  array<string, mixed>|null  $metadata
     * @param  array<string, mixed>|null  $inputSchema
     * @param  array<string, mixed>|null  $expectedOutputSchema
     *
     * @throws JsonException
     */
    public function create(
        string $name,
        ?string $description = null,
        ?array $metadata = null,
        ?array $inputSchema = null,
        ?array $expectedOutputSchema = null,
    ): DatasetResponse {
        $response = $this->transporter->postJson(
            '/api/public/v2/datasets',
            array_filter([
                'name' => $name,
                'description' => $description,
                'metadata' => $metadata,
                'inputSchema' => $inputSchema,
                'expectedOutputSchema' => $expectedOutputSchema,
            ], fn ($value) => $value !== null)
        );

        /** @var array{id: string, name: string, projectId: string, createdAt: string, updatedAt: string, description?: string|null, metadata?: array<string, mixed>|null, inputSchema?: array<string, mixed>|null, expectedOutputSchema?: array<string, mixed>|null} $data */
        $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        return DatasetResponse::fromArray($data);
    }

    /**
     * @throws JsonException
     */
    public function get(string $datasetName): DatasetResponse
    {
        $response = $this->transporter->get(
            sprintf('/api/public/v2/datasets/%s', urlencode($datasetName))
        );

        /** @var array{id: string, name: string, projectId: string, createdAt: string, updatedAt: string, description?: string|null, metadata?: array<string, mixed>|null, inputSchema?: array<string, mixed>|null, expectedOutputSchema?: array<string, mixed>|null} $data */
        $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        return DatasetResponse::fromArray($data);
    }

    /**
     * @throws JsonException
     */
    public function list(?int $page = null, ?int $limit = null): DatasetListResponse
    {
        $response = $this->transporter->get(
            '/api/public/v2/datasets',
            ['query' => array_filter([
                'page' => $page,
                'limit' => $limit,
            ], fn ($value) => $value !== null)]
        );

        /** @var array{data: array<int, array{id: string, name: string, projectId: string, createdAt: string, updatedAt: string, description?: string|null, metadata?: array<string, mixed>|null, inputSchema?: array<string, mixed>|null, expectedOutputSchema?: array<string, mixed>|null}>, meta: array{page: int, limit: int, totalPages: int, totalItems: int}} $data */
        $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        return DatasetListResponse::fromArray($data);
    }

    /**
     * @throws JsonException
     */
    public function getRuns(string $datasetName, ?int $page = null, ?int $limit = null): DatasetRunListResponse
    {
        $response = $this->transporter->get(
            sprintf('/api/public/datasets/%s/runs', urlencode($datasetName)),
            ['query' => array_filter([
                'page' => $page,
                'limit' => $limit,
            ], fn ($value) => $value !== null)]
        );

        /** @var array{data: array<int, array{id: string, name: string, datasetId: string, datasetName: string, createdAt: string, updatedAt: string, description?: string|null, metadata?: array<string, mixed>|null}>, meta: array{page: int, limit: int, totalPages: int, totalItems: int}} $data */
        $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        return DatasetRunListResponse::fromArray($data);
    }
}
