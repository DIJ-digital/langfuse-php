<?php

declare(strict_types=1);

namespace DIJ\Langfuse\ValueObjects;

readonly class PromptListItem
{
    public function __construct(
        public string $name,
        public array $tags,
        public string $lastUpdatedAt,
        public array $versions,
        public array $labels,
        public array $lastConfig,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            tags: $data['tags'] ?? [],
            lastUpdatedAt: $data['lastUpdatedAt'],
            versions: $data['versions'] ?? [],
            labels: $data['labels'] ?? [],
            lastConfig: $data['lastConfig'] ?? [],
        );
    }
}
