<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Responses;

readonly class TextPromptResponse extends BasePromptResponse
{
    /**
     * @param  array<int, string>  $config
     * @param  array<int, string>  $tags
     * @param  array<int, string>  $labels
     * @param  array<int, mixed>  $resolutionGraph
     */
    public function __construct(
        string $id,
        string $name,
        string $prompt,
        string $type,
        array $config,
        array $tags,
        string $projectId,
        string $createdBy,
        string $createdAt,
        string $updatedAt,
        int $version,
        array $labels,
        ?string $isActive = null,
        ?string $commitMessage = null,
        array $resolutionGraph = [],
    ) {
        parent::__construct(
            $prompt,
            $type,
            $id,
            $name,
            $config,
            $tags,
            $projectId,
            $createdBy,
            $createdAt,
            $updatedAt,
            $version,
            $labels,
            $isActive,
            $commitMessage,
            $resolutionGraph,
        );
    }

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
     * resolutionGraph: array<int, mixed>|null,
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
            resolutionGraph: $data['resolutionGraph'] ?? [],
        );
    }
}
