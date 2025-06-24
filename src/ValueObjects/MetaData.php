<?php

declare(strict_types=1);

namespace DIJ\Langfuse\ValueObjects;

readonly class MetaData
{
    public function __construct(
        public int $page,
        public int $limit,
        public int $totalPages,
        public int $totalItems,
    ) {
    }

    /**
     * @param array{page: int, limit: int, totalPages: int, totalItems: int} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            page: $data['page'],
            limit: $data['limit'],
            totalPages: $data['totalPages'],
            totalItems: $data['totalItems'],
        );
    }
}
