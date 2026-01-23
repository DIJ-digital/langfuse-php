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
    public function __construct(private readonly TransporterInterface $transporter) {}

    /**
     * @throws JsonException
     */
    public function create(
        string $traceId,
        string $name,
        float|string $value,
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

        /** @var array{id: string, traceId: string, name: string, value: float|string, dataType: string, source: string, observationId: string|null, comment: string|null, configId: string|null, queueId: string|null, stringValue: string|null, createdAt: string|null, updatedAt: string|null, authorUserId: string|null} $data */
        $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        return ScoreResponse::fromArray($data);
    }

    /**
     * @throws JsonException
     */
    public function get(string $scoreId): ScoreResponse
    {
        $response = $this->transporter->get(sprintf('/api/public/scores/%s', urlencode($scoreId)));

        /** @var array{id: string, traceId: string, name: string, value: float|string, dataType: string, source: string, observationId: string|null, comment: string|null, configId: string|null, queueId: string|null, stringValue: string|null, createdAt: string|null, updatedAt: string|null, authorUserId: string|null} $data */
        $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        return ScoreResponse::fromArray($data);
    }

    /**
     * @throws JsonException
     */
    public function getV2(string $scoreId): ScoreResponse
    {
        $response = $this->transporter->get(sprintf('/api/public/v2/scores/%s', urlencode($scoreId)));

        /** @var array{id: string, traceId: string, name: string, value: float|string, dataType: string, source: string, observationId: string|null, comment: string|null, configId: string|null, queueId: string|null, stringValue: string|null, createdAt: string|null, updatedAt: string|null, authorUserId: string|null} $data */
        $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        return ScoreResponse::fromArray($data);
    }

    /**
     * @param  array<int, string>|null  $scoreIds  Filter by score IDs
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
        ?ScoreDataType $dataType = null,
        ?string $source = null,
        ?string $operator = null,
        ?float $value = null,
        ?string $configId = null,
        ?string $queueId = null,
        ?string $traceId = null,
        ?string $observationId = null,
        ?array $scoreIds = null,
        ?string $sessionId = null,
        ?string $datasetRunId = null,
    ): ScoreListResponse {
        $queryParams = array_filter([
            'page' => $page,
            'limit' => $limit,
            'userId' => $userId,
            'name' => $name,
            'fromTimestamp' => $fromTimestamp,
            'toTimestamp' => $toTimestamp,
            'dataType' => $dataType?->value,
            'source' => $source,
            'operator' => $operator,
            'value' => $value,
            'configId' => $configId,
            'queueId' => $queueId,
            'traceId' => $traceId,
            'observationId' => $observationId,
            'sessionId' => $sessionId,
            'datasetRunId' => $datasetRunId,
        ], static fn (mixed $v): bool => $v !== null);

        if ($scoreIds !== null) {
            $queryParams['scoreIds'] = implode(',', $scoreIds);
        }

        $response = $this->transporter->get('/api/public/scores', ['query' => $queryParams]);

        /** @var array{data: array<int, array{id: string, traceId: string, name: string, value: float|string, dataType: string, source: string, observationId: string|null, comment: string|null, configId: string|null, queueId: string|null, stringValue: string|null, createdAt: string|null, updatedAt: string|null, authorUserId: string|null}>, meta: array{page: int, limit: int, totalPages: int, totalItems: int}} $data */
        $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        return ScoreListResponse::fromArray($data);
    }

    /**
     * @param  array<int, string>|null  $traceTags  Filter scores by trace tags
     *
     * @throws JsonException
     */
    public function listV2(
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
        ], static fn (mixed $v): bool => $v !== null);

        $response = $this->transporter->get('/api/public/v2/scores', ['query' => $queryParams]);

        /** @var array{data: array<int, array{id: string, traceId: string, name: string, value: float|string, dataType: string, source: string, observationId: string|null, comment: string|null, configId: string|null, queueId: string|null, stringValue: string|null, createdAt: string|null, updatedAt: string|null, authorUserId: string|null}>, meta: array{page: int, limit: int, totalPages: int, totalItems: int}} $data */
        $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        return ScoreListResponse::fromArray($data);
    }

    public function delete(string $scoreId): void
    {
        $this->transporter->delete(sprintf('/api/public/scores/%s', urlencode($scoreId)));
    }

    public function deleteV2(string $scoreId): void
    {
        $this->transporter->delete(sprintf('/api/public/v2/scores/%s', urlencode($scoreId)));
    }
}
