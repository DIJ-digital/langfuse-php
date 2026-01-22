<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Responses;

use DIJ\Langfuse\PHP\ValueObjects\MetaData;

readonly class DatasetRunItemListResponse
{
    /**
     * @param  array<int, DatasetRunItemResponse>  $data
     */
    public function __construct(
        public array $data,
        public MetaData $meta,
    ) {}

    /**
     * @param array{
     *     data: array<int, array{id: string, datasetRunId: string, datasetRunName: string, datasetItemId: string, traceId: string, createdAt: string, updatedAt: string, observationId?: string|null}>,
     *     meta: array{page: int, limit: int, totalPages: int, totalItems: int}
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            data: array_map(
                fn (array $item): DatasetRunItemResponse => DatasetRunItemResponse::fromArray($item),
                $data['data']
            ),
            meta: MetaData::fromArray($data['meta']),
        );
    }
}
