<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Responses;

use DIJ\Langfuse\PHP\ValueObjects\MetaData;

readonly class ObservationListResponse
{
    /**
     * @param  array<int, ObservationResponse>  $data
     */
    public function __construct(
        public array $data,
        public MetaData $meta,
    ) {}

    /**
     * @param array{
     *     data: array<int, array{
     *         id: string,
     *         traceId: string|null,
     *         type: string,
     *         name: string|null,
     *         startTime: string,
     *         endTime: string|null,
     *         completionStartTime: string|null,
     *         model: string|null,
     *         modelParameters: array<string, mixed>|null,
     *         input: mixed,
     *         output: mixed,
     *         metadata: mixed,
     *         version: string|null,
     *         parentObservationId: string|null,
     *         level: string|null,
     *         statusMessage: string|null,
     *         promptId: string|null,
     *         promptName: string|null,
     *         promptVersion: int|null,
     *         usage?: array{input: int|null, output: int|null, total: int|null, unit: string|null, inputCost: float|null, outputCost: float|null, totalCost: float|null}|null,
     *         calculatedInputCost?: float|null,
     *         calculatedOutputCost?: float|null,
     *         calculatedTotalCost?: float|null,
     *         latency?: float|null,
     *         timeToFirstToken?: float|null,
     *         projectId: string,
     *         createdAt: string,
     *         updatedAt: string,
     *         environment?: string|null
     *     }>,
     *     meta: array{page: int, limit: int, totalPages: int, totalItems: int}
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            data: array_map(fn (array $item): ObservationResponse => ObservationResponse::fromArray($item), $data['data']),
            meta: MetaData::fromArray($data['meta']),
        );
    }
}
