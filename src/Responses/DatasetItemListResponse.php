<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Responses;

use DIJ\Langfuse\PHP\ValueObjects\MetaData;

readonly class DatasetItemListResponse
{
    /**
     * @param  array<int, DatasetItemResponse>  $data
     */
    public function __construct(
        public array $data,
        public MetaData $meta,
    ) {}

    /**
     * @param array{
     *     data: array<int, array{id: string, status: string, datasetId: string, datasetName: string, createdAt: string, updatedAt: string, input?: mixed, expectedOutput?: mixed, metadata?: array<string, mixed>|null, sourceTraceId?: string|null, sourceObservationId?: string|null}>,
     *     meta: array{page: int, limit: int, totalPages: int, totalItems: int}
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            data: array_map(
                fn (array $item): DatasetItemResponse => DatasetItemResponse::fromArray($item),
                $data['data']
            ),
            meta: MetaData::fromArray($data['meta']),
        );
    }
}
