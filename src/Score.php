<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP;

use DIJ\Langfuse\PHP\Contracts\TransporterInterface;
use DIJ\Langfuse\PHP\Enums\ScoreDataType;
use DIJ\Langfuse\PHP\Responses\ScoreListResponse;
use DIJ\Langfuse\PHP\Responses\ScoreResponse;
use JsonException;

class Score
{
    public function __construct(private readonly TransporterInterface $transporter)
    {
    }

    /**
     * @param array<string, mixed>|null $metadata
     *
     * @throws JsonException
     */
    public function create(
        string $name,
        float|string $value,
        ?string $traceId = null,
        ?ScoreDataType $dataType = null,
        ?string $id = null,
        ?string $observationId = null,
        ?string $comment = null,
        ?string $configId = null,
        ?string $sessionId = null,
        ?string $datasetRunId = null,
        ?array $metadata = null,
        ?string $environment = null,
        ?string $queueId = null,
    ): ScoreResponse {
        $response = $this->transporter->postJson('/api/public/scores', array_filter([
            'id' => $id,
            'traceId' => $traceId,
            'sessionId' => $sessionId,
            'datasetRunId' => $datasetRunId,
            'name' => $name,
            'value' => $value,
            'dataType' => $dataType?->value,
            'observationId' => $observationId,
            'comment' => $comment,
            'configId' => $configId,
            'metadata' => $metadata,
            'environment' => $environment,
            'queueId' => $queueId,
        ], static fn (mixed $v): bool => $v !== null));

        /** @var array{id: string} $data */
        $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        return ScoreResponse::fromArray([
            'traceId' => $traceId,
            'name' => $name,
            'value' => $value,
            'dataType' => ($dataType ?? ScoreDataType::NUMERIC)->value,
            'source' => 'API',
            'observationId' => $observationId,
            'comment' => $comment,
            'configId' => $configId,
            'queueId' => $queueId,
            'stringValue' => null,
            'createdAt' => null,
            'updatedAt' => null,
            'authorUserId' => null,
            ...$data,
        ]);
    }

    /**
     * @throws JsonException
     */
    public function get(string $scoreId): ScoreResponse
    {
        $response = $this->transporter->get(sprintf('/api/public/v2/scores/%s', urlencode($scoreId)));

        /** @var array{id: string, traceId: string|null, name: string, value: float|string, dataType: string, source: string, observationId: string|null, comment: string|null, configId: string|null, queueId: string|null, stringValue: string|null, createdAt: string|null, updatedAt: string|null, authorUserId: string|null} $data */
        $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        return ScoreResponse::fromArray($data);
    }

    /**
     * @param array<string, string>|null $environment Filter scores by environment
     * @param array<int, string>|null $traceTags Filter scores by trace tags
     *
     * @throws JsonException
     */
    public function list(
        ?int $page = null,
        ?int $limit = null,
        ?string $userId = null,
        ?string $name = null,
        ?string $fromTimestamp = null,
        ?string $toTimestamp = null,
        ?array $environment = null,
        ?string $source = null,
        ?string $operator = null,
        ?float $value = null,
        ?string $configId = null,
        ?string $sessionId = null,
        ?string $datasetRunId = null,
        ?string $traceId = null,
        ?string $queueId = null,
        ?ScoreDataType $dataType = null,
        ?array $traceTags = null,
        ?string $fields = null,
        ?string $scoreIds = null,
    ): ScoreListResponse {
        $queryParams = array_filter([
            'page' => $page,
            'limit' => $limit,
            'userId' => $userId,
            'name' => $name,
            'fromTimestamp' => $fromTimestamp,
            'toTimestamp' => $toTimestamp,
            'environment' => $environment,
            'source' => $source,
            'operator' => $operator,
            'value' => $value,
            'configId' => $configId,
            'sessionId' => $sessionId,
            'datasetRunId' => $datasetRunId,
            'traceId' => $traceId,
            'queueId' => $queueId,
            'dataType' => $dataType?->value,
            'traceTags' => $traceTags,
            'fields' => $fields,
            'scoreIds' => $scoreIds,
        ], static fn (mixed $v): bool => $v !== null);

        $response = $this->transporter->get('/api/public/v2/scores', ['query' => $queryParams]);

        /** @var array{data: array<int, array{id: string, traceId: string|null, name: string, value: float|string, dataType: string, source: string, observationId: string|null, comment: string|null, configId: string|null, queueId: string|null, stringValue: string|null, createdAt: string|null, updatedAt: string|null, authorUserId: string|null}>, meta: array{page: int, limit: int, totalPages: int, totalItems: int}} $data */
        $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        return ScoreListResponse::fromArray($data);
    }

    public function delete(string $scoreId): void
    {
        $this->transporter->delete(sprintf('/api/public/scores/%s', urlencode($scoreId)));
    }
}
