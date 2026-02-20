<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Responses;

readonly class TraceResponse
{
    /**
     * @param  array<int, string>  $tags
     * @param  array<int, mixed>  $observations
     * @param  array<int, mixed>  $scores
     */
    public function __construct(
        public string $id,
        public string $timestamp,
        public ?string $name,
        public mixed $input,
        public mixed $output,
        public ?string $sessionId,
        public ?string $release,
        public ?string $version,
        public ?string $userId,
        public mixed $metadata,
        public array $tags,
        public ?bool $public,
        public string $projectId,
        public string $createdAt,
        public string $updatedAt,
        public ?string $externalId = null,
        public ?float $totalCost = null,
        public ?float $latency = null,
        public array $observations = [],
        public array $scores = [],
        public ?string $htmlPath = null,
        public ?string $environment = null,
    ) {}

    /**
     * @param array{
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
     *     externalId?: string|null,
     *     totalCost?: float|null,
     *     latency?: float|null,
     *     observations?: array<int, mixed>,
     *     scores?: array<int, mixed>,
     *     htmlPath?: string|null,
     *     environment?: string|null
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            timestamp: $data['timestamp'],
            name: $data['name'],
            input: $data['input'],
            output: $data['output'],
            sessionId: $data['sessionId'],
            release: $data['release'],
            version: $data['version'],
            userId: $data['userId'],
            metadata: $data['metadata'],
            tags: $data['tags'],
            public: $data['public'],
            projectId: $data['projectId'],
            createdAt: $data['createdAt'],
            updatedAt: $data['updatedAt'],
            externalId: $data['externalId'] ?? null,
            totalCost: $data['totalCost'] ?? null,
            latency: $data['latency'] ?? null,
            observations: $data['observations'] ?? [],
            scores: $data['scores'] ?? [],
            htmlPath: $data['htmlPath'] ?? null,
            environment: $data['environment'] ?? null,
        );
    }
}
