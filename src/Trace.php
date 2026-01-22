<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP;

use DIJ\Langfuse\PHP\Contracts\TransporterInterface;
use DIJ\Langfuse\PHP\Responses\TraceListResponse;
use DIJ\Langfuse\PHP\Responses\TraceResponse;
use JsonException;

class Trace
{
    public function __construct(private readonly TransporterInterface $transporter) {}

    /**
     * Get a trace by ID.
     *
     * @throws JsonException
     */
    public function get(string $traceId): TraceResponse
    {
        $response = $this->transporter->get(
            uri: sprintf('/api/public/traces/%s', urlencode($traceId)),
        );

        /** @var array{
         *     id: string,
         *     timestamp: string,
         *     name: string|null,
         *     input: mixed,
         *     output: mixed,
         *     sessionId: string|null,
         *     release: string|null,
         *     version: string|null,
         *     userId: string|null,
         *     metadata: mixed,
         *     tags: array<int, string>,
         *     public: bool|null,
         *     projectId: string,
         *     createdAt: string,
         *     updatedAt: string,
         *     externalId: string|null,
         *     totalCost: float|null,
         *     latency: float|null,
         *     observations: array<int, mixed>,
         *     scores: array<int, mixed>,
         *     htmlPath: string|null,
         *     environment: string|null
         * } $data
         */
        $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        return TraceResponse::fromArray($data);
    }

    /**
     * List traces with optional filters.
     *
     * @param  int|null  $page  Page number, starts at 1
     * @param  int|null  $limit  Limit of items per page
     * @param  string|null  $userId  Filter by user ID
     * @param  string|null  $name  Filter by name
     * @param  string|null  $sessionId  Filter by session ID
     * @param  string|null  $fromTimestamp  Filter by minimum timestamp (ISO 8601)
     * @param  string|null  $toTimestamp  Filter by maximum timestamp (ISO 8601)
     * @param  string|null  $orderBy  Order by field (e.g., "timestamp")
     * @param  string|null  $tags  Filter by tags (comma-separated)
     * @param  string|null  $version  Filter by version
     * @param  string|null  $release  Filter by release
     * @param  string|null  $environment  Filter by environment
     *
     * @throws JsonException
     */
    public function list(
        ?int $page = null,
        ?int $limit = null,
        ?string $userId = null,
        ?string $name = null,
        ?string $sessionId = null,
        ?string $fromTimestamp = null,
        ?string $toTimestamp = null,
        ?string $orderBy = null,
        ?string $tags = null,
        ?string $version = null,
        ?string $release = null,
        ?string $environment = null,
    ): TraceListResponse {
        $response = $this->transporter->get(
            uri: '/api/public/traces',
            options: ['query' => array_filter([
                'page' => $page,
                'limit' => $limit,
                'userId' => $userId,
                'name' => $name,
                'sessionId' => $sessionId,
                'fromTimestamp' => $fromTimestamp,
                'toTimestamp' => $toTimestamp,
                'orderBy' => $orderBy,
                'tags' => $tags,
                'version' => $version,
                'release' => $release,
                'environment' => $environment,
            ])]
        );

        /** @var array{
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
         *         externalId: string|null,
         *         totalCost: float|null,
         *         latency: float|null,
         *         htmlPath: string|null,
         *         environment: string|null
         *     }>,
         *     meta: array{page: int, limit: int, totalPages: int, totalItems: int}
         * } $data
         */
        $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        return TraceListResponse::fromArray($data);
    }
}
