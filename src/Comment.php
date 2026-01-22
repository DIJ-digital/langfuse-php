<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP;

use DIJ\Langfuse\PHP\Contracts\TransporterInterface;
use DIJ\Langfuse\PHP\Responses\CommentListResponse;
use DIJ\Langfuse\PHP\Responses\CommentResponse;
use JsonException;

class Comment
{
    public function __construct(private readonly TransporterInterface $transporter) {}

    /**
     * @throws JsonException
     */
    public function get(string $commentId): CommentResponse
    {
        $response = $this->transporter->get(
            uri: sprintf('/api/public/comments/%s', urlencode($commentId)),
        );

        /** @var array{
         *     id: string,
         *     content: string,
         *     objectType: string,
         *     objectId: string,
         *     authorUserId: string|null,
         *     createdAt: string,
         *     updatedAt: string,
         *     projectId: string
         * } $data
         */
        $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        return CommentResponse::fromArray($data);
    }

    /**
     * @throws JsonException
     */
    public function list(
        ?int $page = null,
        ?int $limit = null,
        ?string $objectType = null,
        ?string $objectId = null,
        ?string $authorUserId = null,
    ): CommentListResponse {
        $response = $this->transporter->get(
            uri: '/api/public/comments',
            options: ['query' => array_filter([
                'page' => $page,
                'limit' => $limit,
                'objectType' => $objectType,
                'objectId' => $objectId,
                'authorUserId' => $authorUserId,
            ])]
        );

        /** @var array{
         *     data: array<int, array{
         *         id: string,
         *         content: string,
         *         objectType: string,
         *         objectId: string,
         *         authorUserId: string|null,
         *         createdAt: string,
         *         updatedAt: string,
         *         projectId: string
         *     }>,
         *     meta: array{page: int, limit: int, totalPages: int, totalItems: int}
         * } $data
         */
        $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        return CommentListResponse::fromArray($data);
    }

    /**
     * @throws JsonException
     */
    public function create(
        string $content,
        string $objectType,
        string $objectId,
    ): CommentResponse {
        $response = $this->transporter->postJson(
            uri: '/api/public/comments',
            data: [
                'content' => $content,
                'objectType' => $objectType,
                'objectId' => $objectId,
            ],
        );

        /** @var array{
         *     id: string,
         *     content: string,
         *     objectType: string,
         *     objectId: string,
         *     authorUserId: string|null,
         *     createdAt: string,
         *     updatedAt: string,
         *     projectId: string
         * } $data
         */
        $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        return CommentResponse::fromArray($data);
    }
}
