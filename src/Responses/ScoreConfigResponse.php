<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Responses;

readonly class ScoreConfigResponse
{
    /**
     * @param  array<int, array{value: float, label: string}>|null  $categories
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $dataType,
        public bool $isArchived,
        public ?float $minValue,
        public ?float $maxValue,
        public ?array $categories,
        public ?string $description,
        public string $projectId,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    /**
     * @param array{
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
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            dataType: $data['dataType'],
            isArchived: $data['isArchived'],
            minValue: $data['minValue'],
            maxValue: $data['maxValue'],
            categories: $data['categories'],
            description: $data['description'],
            projectId: $data['projectId'],
            createdAt: $data['createdAt'],
            updatedAt: $data['updatedAt'],
        );
    }
}
