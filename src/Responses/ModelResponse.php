<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Responses;

readonly class ModelResponse
{
    public function __construct(
        public string $id,
        public string $modelName,
        public string $matchPattern,
        public ?string $startDate,
        public ?string $unit,
        public ?float $inputPrice,
        public ?float $outputPrice,
        public ?float $totalPrice,
        public ?string $tokenizerId,
        public mixed $tokenizerConfig,
        public bool $isLangfuseManaged,
        public string $createdAt,
    ) {}

    /**
     * @param array{
     *     id: string,
     *     modelName: string,
     *     matchPattern: string,
     *     startDate: string|null,
     *     unit: string|null,
     *     inputPrice: float|null,
     *     outputPrice: float|null,
     *     totalPrice: float|null,
     *     tokenizerId: string|null,
     *     tokenizerConfig: mixed,
     *     isLangfuseManaged: bool,
     *     createdAt: string
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            modelName: $data['modelName'],
            matchPattern: $data['matchPattern'],
            startDate: $data['startDate'],
            unit: $data['unit'],
            inputPrice: $data['inputPrice'],
            outputPrice: $data['outputPrice'],
            totalPrice: $data['totalPrice'],
            tokenizerId: $data['tokenizerId'],
            tokenizerConfig: $data['tokenizerConfig'],
            isLangfuseManaged: $data['isLangfuseManaged'],
            createdAt: $data['createdAt'],
        );
    }
}
