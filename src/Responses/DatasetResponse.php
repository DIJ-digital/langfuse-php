<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Responses;

readonly class DatasetResponse
{
    /**
     * @param  array<string, mixed>|null  $metadata
     * @param  array<string, mixed>|null  $inputSchema
     * @param  array<string, mixed>|null  $expectedOutputSchema
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $projectId,
        public string $createdAt,
        public string $updatedAt,
        public ?string $description = null,
        public ?array $metadata = null,
        public ?array $inputSchema = null,
        public ?array $expectedOutputSchema = null,
    ) {}

    /**
     * @param array{
     *     id: string,
     *     name: string,
     *     projectId: string,
     *     createdAt: string,
     *     updatedAt: string,
     *     description?: string|null,
     *     metadata?: array<string, mixed>|null,
     *     inputSchema?: array<string, mixed>|null,
     *     expectedOutputSchema?: array<string, mixed>|null
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            projectId: $data['projectId'],
            createdAt: $data['createdAt'],
            updatedAt: $data['updatedAt'],
            description: $data['description'] ?? null,
            metadata: $data['metadata'] ?? null,
            inputSchema: $data['inputSchema'] ?? null,
            expectedOutputSchema: $data['expectedOutputSchema'] ?? null,
        );
    }
}
