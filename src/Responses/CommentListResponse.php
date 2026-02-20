<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Responses;

use DIJ\Langfuse\PHP\ValueObjects\MetaData;

readonly class CommentListResponse
{
    /**
     * @param array<int, CommentResponse> $data
     */
    public function __construct(
        public array $data,
        public MetaData $meta,
    ) {
    }

    /**
     * @param array{
     *     data: array<int, array{
     *         id: string,
     *         content: string,
     *         objectType: string,
     *         objectId: string,
     *         authorUserId: string|null,
     *         createdAt: string,
     *         updatedAt: string,
     *         projectId: string
     *     }>,
     *     meta: array{page: int, limit: int, totalPages: int, totalItems: int}
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            data: array_map(
                CommentResponse::fromArray(...),
                $data['data']
            ),
            meta: MetaData::fromArray($data['meta']),
        );
    }
}
