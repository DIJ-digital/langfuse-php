<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Responses;

readonly class DatasetRunItemResponse
{
    public function __construct(
        public string $id,
        public string $datasetRunId,
        public string $datasetRunName,
        public string $datasetItemId,
        public string $traceId,
        public string $createdAt,
        public string $updatedAt,
        public ?string $observationId = null,
    ) {}

    /**
     * @param array{
     *     id: string,
     *     datasetRunId: string,
     *     datasetRunName: string,
     *     datasetItemId: string,
     *     traceId: string,
     *     createdAt: string,
     *     updatedAt: string,
     *     observationId?: string|null
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            datasetRunId: $data['datasetRunId'],
            datasetRunName: $data['datasetRunName'],
            datasetItemId: $data['datasetItemId'],
            traceId: $data['traceId'],
            createdAt: $data['createdAt'],
            updatedAt: $data['updatedAt'],
            observationId: $data['observationId'] ?? null,
        );
    }
}
