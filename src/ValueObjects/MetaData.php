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

    /**
     * @param  array<string, int>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            page: (int) ($data['page'] ?? 0),
            limit: (int) ($data['limit'] ?? 0),
            totalPages: (int) ($data['totalPages'] ?? 0),
            totalItems: (int) ($data['totalItems'] ?? 0),
        );
    }
}
