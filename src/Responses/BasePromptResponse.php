<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Responses;

use DIJ\Langfuse\PHP\Concerns\IsCompilable;

abstract readonly class BasePromptResponse
{
    use IsCompilable;

    /**
     * @param  ($type is 'text' ? string : array<int, array{role:string, content:string}>)  $prompt
     * @param  array<int, string>  $config
     * @param  array<int, string>  $tags
     * @param  array<int, string>  $labels
     */
    public function __construct(
        public string|array $prompt,
        public string $type,
        public ?string $id = null,
        public ?string $name = null,
        public array $config = [],
        public array $tags = [],
        public ?string $projectId = null,
        public ?string $createdBy = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public ?int $version = null,
        public array $labels = [],
        public ?string $isActive = null,
        public ?string $commitMessage = null,
        public ?string $resolutionGraph = null,
    ) {}
}
