<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Responses;

use DIJ\Langfuse\PHP\ValueObjects\MetaData;
use DIJ\Langfuse\PHP\ValueObjects\PaginationData;
use DIJ\Langfuse\PHP\ValueObjects\PromptListItem;

readonly class PromptListResponse
{
    /**
     * @param  array<int, PromptListItem>  $data
     */
    public function __construct(
        public array $data,
        public MetaData $meta,
        public PaginationData $pagination,
    ) {}

    /**
     * @param array{
     *     data: array<int, array{name: string, tags: array<int, string>, lastUpdatedAt: string, versions: array<int, int>, labels: array<int, string>, lastConfig: array<string, mixed>}>,
     *     meta: array{page: int, limit: int, totalPages: int, totalItems: int},
     *     pagination: array{page: int, limit: int, totalPages: int, totalItems: int}
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            data: array_map(fn (array $data): PromptListItem => PromptListItem::fromArray($data), $data['data']),
            meta: MetaData::fromArray($data['meta']),
            pagination: PaginationData::fromArray($data['pagination']),
        );
    }
}
