<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\ValueObjects;

use DIJ\Langfuse\PHP\Enums\EventType;

class IngestionEvent
{
    public function __construct(
        public readonly string $id,
        public readonly EventType $type,
        public readonly array $body,
        public readonly ?string $timestamp = null,
        public readonly ?array $metadata = null,
    ) {}

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'type' => $this->type->value,
            'body' => $this->body,
        ];

        if ($this->timestamp !== null) {
            $data['timestamp'] = $this->timestamp;
        }

        if ($this->metadata !== null) {
            $data['metadata'] = $this->metadata;
        }

        return $data;
    }
}
