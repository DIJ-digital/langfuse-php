<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Responses;

use DIJ\Langfuse\PHP\ValueObjects\MetaData;

readonly class ScoreListResponse
{
    /**
     * @param array<int, ScoreResponse> $data
     */
    public function __construct(
        public array $data,
        public MetaData $meta,
    ) {
    }

    /**
     * @param array{
     *     data: array<int, array{id: string, traceId: string|null, name: string, value: float|string, dataType: string, source: string, observationId: string|null, comment: string|null, configId: string|null, queueId: string|null, stringValue: string|null, createdAt: string|null, updatedAt: string|null, authorUserId: string|null}>,
     *     meta: array{page: int, limit: int, totalPages: int, totalItems: int}
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            data: array_map(fn (array $item): ScoreResponse => ScoreResponse::fromArray($item), $data['data']),
            meta: MetaData::fromArray($data['meta']),
        );
    }
}
