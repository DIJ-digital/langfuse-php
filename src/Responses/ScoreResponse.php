<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Responses;

use DIJ\Langfuse\PHP\Enums\ScoreDataType;
use DIJ\Langfuse\PHP\Enums\ScoreSource;

readonly class ScoreResponse
{
    public function __construct(
        public string $id,
        public string $traceId,
        public string $name,
        public float|string $value,
        public ScoreDataType $dataType,
        public ScoreSource $source,
        public ?string $observationId = null,
        public ?string $comment = null,
        public ?string $configId = null,
        public ?string $queueId = null,
        public ?string $stringValue = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public ?string $authorUserId = null,
    ) {}

    /**
     * @param array{
     *     id: string,
     *     traceId: string,
     *     name: string,
     *     value: float|string,
     *     dataType: string,
     *     source: string,
     *     observationId: string|null,
     *     comment: string|null,
     *     configId: string|null,
     *     queueId: string|null,
     *     stringValue: string|null,
     *     createdAt: string|null,
     *     updatedAt: string|null,
     *     authorUserId: string|null,
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            traceId: $data['traceId'],
            name: $data['name'],
            value: $data['value'],
            dataType: ScoreDataType::from($data['dataType']),
            source: ScoreSource::from($data['source']),
            observationId: $data['observationId'] ?? null,
            comment: $data['comment'] ?? null,
            configId: $data['configId'] ?? null,
            queueId: $data['queueId'] ?? null,
            stringValue: $data['stringValue'] ?? null,
            createdAt: $data['createdAt'] ?? null,
            updatedAt: $data['updatedAt'] ?? null,
            authorUserId: $data['authorUserId'] ?? null,
        );
    }
}
