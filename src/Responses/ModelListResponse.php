<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Responses;

use DIJ\Langfuse\PHP\ValueObjects\MetaData;

readonly class ModelListResponse
{
    /**
     * @param  array<int, ModelResponse>  $data
     */
    public function __construct(
        public array $data,
        public MetaData $meta,
    ) {}

    /**
     * @param array{
     *     data: array<int, array{
     *         id: string,
     *         modelName: string,
     *         matchPattern: string,
     *         startDate: string|null,
     *         unit: string|null,
     *         inputPrice: float|null,
     *         outputPrice: float|null,
     *         totalPrice: float|null,
     *         tokenizerId: string|null,
     *         tokenizerConfig: mixed,
     *         isLangfuseManaged: bool,
     *         createdAt: string
     *     }>,
     *     meta: array{page: int, limit: int, totalPages: int, totalItems: int}
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            data: array_map(
                fn (array $item): ModelResponse => ModelResponse::fromArray($item),
                $data['data']
            ),
            meta: MetaData::fromArray($data['meta']),
        );
    }
}
