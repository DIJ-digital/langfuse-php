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
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            page: $data['page'] ?? 0,
            limit: $data['limit'] ?? 0,
            totalPages: $data['totalPages'] ?? 0,
            totalItems: $data['totalItems'] ?? 0,
        );
    }
}
