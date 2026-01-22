<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\ValueObjects;

readonly class PromptListItem
{
    /**
     * @param  array<int, string>  $tags
     * @param  array<int, int>  $versions
     * @param  array<int, string>  $labels
     * @param  array<string, mixed>  $lastConfig
     */
    public function __construct(
        public string $name,
        public array $tags,
        public string $lastUpdatedAt,
        public array $versions,
        public array $labels,
        public array $lastConfig,
    ) {}

    /**
     * @param  array{name: string, tags: array<int, string>, lastUpdatedAt: string, versions: array<int, int>, labels: array<int, string>, lastConfig: array<string, mixed>}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            tags: $data['tags'],
            lastUpdatedAt: $data['lastUpdatedAt'],
            versions: $data['versions'],
            labels: $data['labels'],
            lastConfig: $data['lastConfig'],
        );
    }
}
