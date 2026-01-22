<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Responses;

readonly class SessionResponse
{
    /**
     * @param  array<int, mixed>  $traces
     */
    public function __construct(
        public string $id,
        public string $createdAt,
        public string $projectId,
        public ?string $environment = null,
        public ?bool $bookmarked = null,
        public ?bool $public = null,
        public array $traces = [],
    ) {}

    /**
     * @param array{
     *     id: string,
     *     createdAt: string,
     *     projectId: string,
     *     environment?: string|null,
     *     bookmarked?: bool|null,
     *     public?: bool|null,
     *     traces?: array<int, mixed>
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            createdAt: $data['createdAt'],
            projectId: $data['projectId'],
            environment: $data['environment'] ?? null,
            bookmarked: $data['bookmarked'] ?? null,
            public: $data['public'] ?? null,
            traces: $data['traces'] ?? [],
        );
    }
}
