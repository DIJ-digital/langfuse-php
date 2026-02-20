<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Responses;

use DIJ\Langfuse\PHP\Enums\DatasetStatus;

readonly class DatasetItemResponse
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public string $id,
        public DatasetStatus $status,
        public string $datasetId,
        public string $datasetName,
        public string $createdAt,
        public string $updatedAt,
        public mixed $input = null,
        public mixed $expectedOutput = null,
        public ?array $metadata = null,
        public ?string $sourceTraceId = null,
        public ?string $sourceObservationId = null,
    ) {}

    /**
     * @param array{
     *     id: string,
     *     status: string,
     *     datasetId: string,
     *     datasetName: string,
     *     createdAt: string,
     *     updatedAt: string,
     *     input?: mixed,
     *     expectedOutput?: mixed,
     *     metadata?: array<string, mixed>|null,
     *     sourceTraceId?: string|null,
     *     sourceObservationId?: string|null
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            status: DatasetStatus::from($data['status']),
            datasetId: $data['datasetId'],
            datasetName: $data['datasetName'],
            createdAt: $data['createdAt'],
            updatedAt: $data['updatedAt'],
            input: $data['input'] ?? null,
            expectedOutput: $data['expectedOutput'] ?? null,
            metadata: $data['metadata'] ?? null,
            sourceTraceId: $data['sourceTraceId'] ?? null,
            sourceObservationId: $data['sourceObservationId'] ?? null,
        );
    }
}
