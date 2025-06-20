<?php

declare(strict_types=1);

namespace DIJ\Langfuse\Responses;

use DIJ\Langfuse\ValueObjects\MetaData;
use DIJ\Langfuse\ValueObjects\PaginationData;
use DIJ\Langfuse\ValueObjects\PromptListItem;

final readonly class PromptListResponse
{
    public function __construct(
        public array $data,
        public MetaData $meta,
        public PaginationData $pagination,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            data: array_map(fn (array $prompt) => PromptListItem::fromArray($prompt), $data['data'] ?? []),
            meta: MetaData::fromArray($data['meta']) ?? [],
            pagination: PaginationData::fromArray($data['pagination']) ?? [],
        );
    }
}
