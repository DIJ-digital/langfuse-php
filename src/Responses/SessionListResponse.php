<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Responses;

use DIJ\Langfuse\PHP\ValueObjects\MetaData;

readonly class SessionListResponse
{
    /**
     * @param  array<int, SessionResponse>  $data
     */
    public function __construct(
        public array $data,
        public MetaData $meta,
    ) {}

    /**
     * @param array{
     *     data: array<int, array{
     *         id: string,
     *         createdAt: string,
     *         projectId: string,
     *         environment?: string|null,
     *         bookmarked?: bool|null,
     *         public?: bool|null
     *     }>,
     *     meta: array{page: int, limit: int, totalPages: int, totalItems: int}
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            data: array_map(fn (array $item): SessionResponse => SessionResponse::fromArray($item), $data['data']),
            meta: MetaData::fromArray($data['meta']),
        );
    }
}
