<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP;

use DIJ\Langfuse\PHP\Contracts\TransporterInterface;
use DIJ\Langfuse\PHP\Responses\SessionListResponse;
use DIJ\Langfuse\PHP\Responses\SessionResponse;
use JsonException;

class Session
{
    public function __construct(private readonly TransporterInterface $transporter) {}

    /**
     * @throws JsonException
     */
    public function get(string $sessionId): SessionResponse
    {
        $response = $this->transporter->get(
            uri: sprintf('/api/public/sessions/%s', urlencode($sessionId)),
        );

        /** @var array{
         *     id: string,
         *     createdAt: string,
         *     projectId: string,
         *     environment: string|null,
         *     bookmarked: bool|null,
         *     public: bool|null,
         *     traces: array<int, mixed>
         * } $data
         */
        $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        return SessionResponse::fromArray($data);
    }

    /**
     * @throws JsonException
     */
    public function list(
        ?int $page = null,
        ?int $limit = null,
        ?string $fromTimestamp = null,
        ?string $toTimestamp = null,
        ?array $environment = null,
    ): SessionListResponse {
        $response = $this->transporter->get(
            uri: '/api/public/sessions',
            options: ['query' => array_filter([
                'page' => $page,
                'limit' => $limit,
                'fromTimestamp' => $fromTimestamp,
                'toTimestamp' => $toTimestamp,
                'environment' => $environment,
            ])]
        );

        /** @var array{
         *     data: array<int, array{
         *         id: string,
         *         createdAt: string,
         *         projectId: string,
         *         environment: string|null,
         *         bookmarked: bool|null,
         *         public: bool|null
         *     }>,
         *     meta: array{page: int, limit: int, totalPages: int, totalItems: int}
         * } $data
         */
        $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        return SessionListResponse::fromArray($data);
    }
}
