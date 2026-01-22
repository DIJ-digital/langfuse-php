<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Responses;

use DIJ\Langfuse\PHP\ValueObjects\MetaData;

readonly class DatasetRunListResponse
{
    /**
     * @param  array<int, DatasetRunResponse>  $data
     */
    public function __construct(
        public array $data,
        public MetaData $meta,
    ) {}

    /**
     * @param array{
     *     data: array<int, array{id: string, name: string, datasetId: string, datasetName: string, createdAt: string, updatedAt: string, description?: string|null, metadata?: array<string, mixed>|null}>,
     *     meta: array{page: int, limit: int, totalPages: int, totalItems: int}
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            data: array_map(
                fn (array $item): DatasetRunResponse => DatasetRunResponse::fromArray($item),
                $data['data']
            ),
            meta: MetaData::fromArray($data['meta']),
        );
    }
}
