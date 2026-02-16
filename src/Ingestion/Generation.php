<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Ingestion;

use DIJ\Langfuse\PHP\Otlp\Serializer;
use DIJ\Langfuse\PHP\Otlp\SpanData;

class Generation
{
    public function __construct(
        private readonly SpanData $data,
    ) {}

    public string $id {
        get => $this->data->spanId;
    }

    /**
     * Update this generation with additional attributes.
     *
     * @param  array<string, mixed>|string|null  $input
     * @param  array<string, mixed>|string|null  $output
     * @param  array<string, mixed>|null  $modelParameters
     * @param  array<string, mixed>|null  $metadata
     */
    public function update(
        array|string|null $input = null,
        array|string|null $output = null,
        ?string $model = null,
        ?array $modelParameters = null,
        ?array $metadata = null,
    ): self {
        $attrs = array_filter([
            'langfuse.observation.input' => Serializer::serializeValue($input),
            'langfuse.observation.output' => Serializer::serializeValue($output),
            'langfuse.observation.model.name' => $model,
            'langfuse.observation.model.parameters' => $modelParameters !== null ? json_encode($modelParameters, JSON_THROW_ON_ERROR) : null,
        ], static fn (mixed $v): bool => $v !== null);

        if ($metadata !== null) {
            $attrs = array_merge($attrs, Serializer::flattenMetadata('langfuse.observation.metadata', $metadata));
        }

        $this->data->setAttributes($attrs);

        return $this;
    }
}
