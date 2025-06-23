<?php

declare(strict_types=1);

namespace DIJ\Langfuse\Responses;

readonly class PromptResponse
{
    /**
     * @param  array<int, string>  $config
     * @param  array<int, string>  $tags
     * @param  array<int, string>  $labels
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $prompt,
        public string $type,
        public array $config,
        public array $tags,
        public string $projectId,
        public string $createdBy,
        public string $createdAt,
        public string $updatedAt,
        public int $version,
        public array $labels,
        public ?string $isActive = null,
        public ?string $commitMessage = null,
        public ?string $resolutionGraph = null,
    ) {}

    /**
     * @param array{
     * id: string,
     * name: string,
     * prompt: string,
     * type: string,
     * config: array<int, string>,
     * tags: array<int, string>,
     * projectId: string,
     * createdBy: string,
     * createdAt: string,
     * updatedAt: string,
     * version: int,
     * labels: array<int,string>,
     * isActive: string|null,
     * commitMessage: string|null,
     * resolutionGraph: string|null,
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            prompt: $data['prompt'],
            type: $data['type'],
            config: $data['config'],
            tags: $data['tags'],
            projectId: $data['projectId'],
            createdBy: $data['createdBy'],
            createdAt: $data['createdAt'],
            updatedAt: $data['updatedAt'],
            version: $data['version'],
            labels: $data['labels'],
            isActive: $data['isActive'] ?? null,
            commitMessage: $data['commitMessage'] ?? null,
            resolutionGraph: $data['resolutionGraph'] ?? null,
        );
    }
}
