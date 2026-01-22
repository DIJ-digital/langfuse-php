<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Responses;

readonly class CommentResponse
{
    public function __construct(
        public string $id,
        public string $content,
        public string $objectType,
        public string $objectId,
        public ?string $authorUserId,
        public string $createdAt,
        public string $updatedAt,
        public string $projectId,
    ) {}

    /**
     * @param array{
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
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            content: $data['content'],
            objectType: $data['objectType'],
            objectId: $data['objectId'],
            authorUserId: $data['authorUserId'],
            createdAt: $data['createdAt'],
            updatedAt: $data['updatedAt'],
            projectId: $data['projectId'],
        );
    }
}
