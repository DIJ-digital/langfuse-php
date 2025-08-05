<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Responses;

readonly class ChatPromptResponse extends BasePromptResponse
{
    /**
     * @param array<int, array{role: string, content: string}> $prompt
     * @param array<int, string> $config
     * @param array<int, string> $tags
     * @param array<int, string> $labels
     * @param array<int, mixed> $resolutionGraph
     */
    public function __construct(
        string $id,
        string $name,
        array $prompt,
        string $type,
        array $config,
        array $tags,
        string $projectId,
        string $createdBy,
        string $createdAt,
        string $updatedAt,
        int $version,
        array $labels,
        ?bool $isActive = null,
        ?string $commitMessage = null,
        array $resolutionGraph = [],
    ) {
        parent::__construct(
            prompt: $prompt,
            type: $type,
            id: $id,
            name: $name,
            config: $config,
            tags: $tags,
            projectId: $projectId,
            createdBy: $createdBy,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            version: $version,
            labels: $labels,
            isActive: $isActive,
            commitMessage: $commitMessage,
            resolutionGraph: $resolutionGraph,
        );
    }

    /**
     * @param array{
     * id: string,
     * name: string,
     * prompt: array<int, array{role: string, content: string}>,
     * type: string,
     * config: array<int, string>,
     * tags: array<int, string>,
     * projectId: string,
     * createdBy: string,
     * createdAt: string,
     * updatedAt: string,
     * version: int,
     * labels: array<int,string>,
     * isActive: bool|null,
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
            isActive: isset($data['isActive']) ? filter_var($data['isActive'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null,
            commitMessage: $data['commitMessage'] ?? null,
            resolutionGraph: $data['resolutionGraph'] ?? [],
        );
    }
}
