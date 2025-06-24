<?php

declare(strict_types=1);

namespace DIJ\Langfuse\Responses;

use DIJ\Langfuse\Concerns\IsCompilable;

abstract readonly class BasePromptResponse
{
    use IsCompilable;

    /**
     * @param ($type is 'text' ? string : array<int, array{role:string, content:string}>) $prompt
     * @param array<int, string> $config
     * @param array<int, string> $tags
     * @param array<int, string> $labels
     */
    public function __construct(
        public string $id,
        public string $name,
        public string|array $prompt,
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
    ) {
    }
}
