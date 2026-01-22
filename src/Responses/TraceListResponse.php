<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Responses;

use DIJ\Langfuse\PHP\ValueObjects\MetaData;

readonly class TraceListResponse
{
    /**
     * @param  array<int, TraceResponse>  $data
     */
    public function __construct(
        public array $data,
        public MetaData $meta,
    ) {}

    /**
     * @param array{
     *     data: array<int, array{
     *         id: string,
     *         timestamp: string,
     *         name: string|null,
     *         input: mixed,
     *         output: mixed,
     *         sessionId: string|null,
     *         release: string|null,
     *         version: string|null,
     *         userId: string|null,
     *         metadata: mixed,
     *         tags: array<int, string>,
     *         public: bool|null,
     *         projectId: string,
     *         createdAt: string,
     *         updatedAt: string,
     *         externalId?: string|null,
     *         totalCost?: float|null,
     *         latency?: float|null,
     *         htmlPath?: string|null,
     *         environment?: string|null
     *     }>,
     *     meta: array{page: int, limit: int, totalPages: int, totalItems: int}
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            data: array_map(fn (array $item): TraceResponse => TraceResponse::fromArray($item), $data['data']),
            meta: MetaData::fromArray($data['meta']),
        );
    }
}
