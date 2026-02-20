<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Responses;

use DIJ\Langfuse\PHP\ValueObjects\MetaData;

readonly class ScoreConfigListResponse
{
    /**
     * @param  array<int, ScoreConfigResponse>  $data
     */
    public function __construct(
        public array $data,
        public MetaData $meta,
    ) {}

    /**
     * @param array{
     *     data: array<int, array{
     *         id: string,
     *         name: string,
     *         dataType: string,
     *         isArchived: bool,
     *         minValue: float|null,
     *         maxValue: float|null,
     *         categories: array<int, array{value: float, label: string}>|null,
     *         description: string|null,
     *         projectId: string,
     *         createdAt: string,
     *         updatedAt: string
     *     }>,
     *     meta: array{page: int, limit: int, totalPages: int, totalItems: int}
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            data: array_map(fn (array $item): ScoreConfigResponse => ScoreConfigResponse::fromArray($item), $data['data']),
            meta: MetaData::fromArray($data['meta']),
        );
    }
}
