<?php

declare(strict_types=1);

namespace DIJ\Langfuse\Responses;

abstract readonly class BasePromptResponse
{
    /**
     * @param  array<int, string>  $config
     * @param  array<int, string>  $tags
     * @param  array<int, string>  $labels
     */
    public function __construct(
        public string $id,
        public string $name,
        public mixed $prompt,
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
}
