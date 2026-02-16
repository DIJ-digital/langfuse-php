<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Ingestion;

use Closure;
use DIJ\Langfuse\PHP\Enums\SpanKind;
use DIJ\Langfuse\PHP\Otlp\Serializer;
use DIJ\Langfuse\PHP\Otlp\SpanData;

class Span
{
    /**
     * @param  Closure(SpanData): void  $register
     * @param  Closure(): string  $generateSpanId
     */
    public function __construct(
        private readonly SpanData $data,
        private readonly Closure $register,
        private readonly Closure $generateSpanId,
        private readonly string $environment,
    ) {}

    public string $id {
        get => $this->data->spanId;
    }

    /**
     * Update this span with additional attributes.
     *
     * @param  array<string, mixed>|string|null  $input
     * @param  array<string, mixed>|string|null  $output
     * @param  array<string, mixed>|null  $metadata
     */
    public function update(
        ?string $name = null,
        array|string|null $input = null,
        array|string|null $output = null,
        ?string $endTime = null,
        ?array $metadata = null,
    ): self {
        $attrs = array_filter([
            'langfuse.observation.input' => Serializer::serializeValue($input),
            'langfuse.observation.output' => Serializer::serializeValue($output),
        ], static fn (mixed $v): bool => $v !== null);

        if ($name !== null) {
            $attrs['langfuse.observation.name'] = $name;
        }

        if ($metadata !== null) {
            $attrs = array_merge($attrs, Serializer::flattenMetadata('langfuse.observation.metadata', $metadata));
        }

        if ($endTime !== null) {
            $this->data->setEndTime(Serializer::toNanoTimestamp($endTime));
        }

        $this->data->setAttributes($attrs);

        return $this;
    }

    /**
     * Create a child span nested under this span.
     *
     * @param  array<string, mixed>|string|null  $input
     * @param  array<string, mixed>|string|null  $output
     * @param  array<string, mixed>|null  $metadata
     */
    public function span(
        string $name,
        array|string|null $input = null,
        array|string|null $output = null,
        ?string $startTime = null,
        ?string $endTime = null,
        ?array $metadata = null,
        ?string $spanId = null,
    ): self {
        $spanId = $spanId ?? ($this->generateSpanId)();
        $startNano = $startTime !== null ? Serializer::toNanoTimestamp($startTime) : Serializer::nowNano();

        $spanData = new SpanData(
            traceId: $this->data->traceId,
            spanId: $spanId,
            parentSpanId: $this->data->spanId,
            name: $name,
            kind: SpanKind::SPAN,
            startTimeNano: $startNano,
        );

        $attrs = array_filter([
            'langfuse.observation.type' => 'span',
            'langfuse.environment' => $this->environment,
            'langfuse.observation.input' => Serializer::serializeValue($input),
            'langfuse.observation.output' => Serializer::serializeValue($output),
        ], static fn (mixed $v): bool => $v !== null);

        if ($metadata !== null) {
            $attrs = array_merge($attrs, Serializer::flattenMetadata('langfuse.observation.metadata', $metadata));
        }

        if ($endTime !== null) {
            $spanData->setEndTime(Serializer::toNanoTimestamp($endTime));
        }

        $spanData->setAttributes($attrs);
        ($this->register)($spanData);

        return new self(
            data: $spanData,
            register: $this->register,
            generateSpanId: $this->generateSpanId,
            environment: $this->environment,
        );
    }

    /**
     * Create a child generation nested under this span.
     *
     * @param  array<string, mixed>|string  $input
     * @param  array<string, mixed>|string  $output
     * @param  array<string, mixed>|null  $modelParameters
     * @param  array<string, mixed>|null  $metadata
     */
    public function generation(
        array|string $input,
        array|string $output,
        string $name,
        ?string $model = null,
        ?array $modelParameters = null,
        ?string $promptName = null,
        ?int $promptVersion = null,
        ?array $metadata = null,
        ?string $generationId = null,
    ): Generation {
        $generationId = $generationId ?? ($this->generateSpanId)();

        $spanData = new SpanData(
            traceId: $this->data->traceId,
            spanId: $generationId,
            parentSpanId: $this->data->spanId,
            name: $name,
            kind: SpanKind::GENERATION,
            startTimeNano: Serializer::nowNano(),
        );

        $attrs = array_filter([
            'langfuse.observation.type' => 'generation',
            'langfuse.environment' => $this->environment,
            'langfuse.observation.input' => Serializer::serializeValue($input),
            'langfuse.observation.output' => Serializer::serializeValue($output),
            'langfuse.observation.model.name' => $model,
            'langfuse.observation.model.parameters' => $modelParameters !== null ? json_encode($modelParameters, JSON_THROW_ON_ERROR) : null,
            'langfuse.observation.prompt.name' => $promptName,
            'langfuse.observation.prompt.version' => $promptVersion,
        ], static fn (mixed $v): bool => $v !== null);

        if ($metadata !== null) {
            $attrs = array_merge($attrs, Serializer::flattenMetadata('langfuse.observation.metadata', $metadata));
        }

        $spanData->setAttributes($attrs);
        ($this->register)($spanData);

        return new Generation(data: $spanData);
    }
}
