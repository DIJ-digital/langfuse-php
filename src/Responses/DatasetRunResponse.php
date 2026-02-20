<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Responses;

readonly class DatasetRunResponse
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $datasetId,
        public string $datasetName,
        public string $createdAt,
        public string $updatedAt,
        public ?string $description = null,
        public ?array $metadata = null,
    ) {}

    /**
     * @param array{
     *     id: string,
     *     name: string,
     *     datasetId: string,
     *     datasetName: string,
     *     createdAt: string,
     *     updatedAt: string,
     *     description?: string|null,
     *     metadata?: array<string, mixed>|null
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            datasetId: $data['datasetId'],
            datasetName: $data['datasetName'],
            createdAt: $data['createdAt'],
            updatedAt: $data['updatedAt'],
            description: $data['description'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }
}
