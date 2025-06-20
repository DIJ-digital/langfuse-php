<?php

declare(strict_types=1);

namespace DIJ\Langfuse\Responses;

final readonly class PromptResponse
{
    public function __construct(
        public string $id,
        public string $name,
        public string $prompt,
        public string $type,
        public array $config,
        public array $tags,
        public string $projectId,
        public string $createdAt,
        public string $updatedAt,
        public ?int $version = null,
        public ?string $label = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            prompt: $data['prompt'],
            type: $data['type'],
            config: $data['config'] ?? [],
            tags: $data['tags'] ?? [],
            projectId: $data['projectId'],
            createdAt: $data['createdAt'],
            updatedAt: $data['updatedAt'],
            version: $data['version'] ?? null,
            label: $data['label'] ?? null,
        );
    }
}
