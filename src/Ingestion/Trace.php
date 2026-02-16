<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Ingestion;

use Closure;
use DIJ\Langfuse\PHP\Enums\SpanKind;
use DIJ\Langfuse\PHP\Otlp\Serializer;
use DIJ\Langfuse\PHP\Otlp\SpanData;

class Trace
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
        get => $this->data->traceId;
    }

    /**
     * Update this trace with additional attributes.
     *
     * @param  array<string, mixed>|string|null  $input
     * @param  array<string, mixed>|string|null  $output
     * @param  array<string, mixed>|null  $metadata
     * @param  array<int, string>|null  $tags
     */
    public function update(
        ?string $name = null,
        array|string|null $input = null,
        array|string|null $output = null,
        ?string $sessionId = null,
        ?string $userId = null,
        ?array $metadata = null,
        ?array $tags = null,
    ): self {
        $attrs = array_filter([
            'langfuse.trace.name' => $name,
            'user.id' => $userId,
            'session.id' => $sessionId,
            'langfuse.trace.input' => Serializer::serializeValue($input),
            'langfuse.trace.output' => Serializer::serializeValue($output),
            'langfuse.trace.tags' => $tags,
        ], static fn (mixed $v): bool => $v !== null);

        if ($metadata !== null) {
            $attrs = array_merge($attrs, Serializer::flattenMetadata('langfuse.trace.metadata', $metadata));
        }

        $this->data->setAttributes($attrs);

        return $this;
    }

    /**
     * Create a child span on this trace.
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
    ): Span {
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

        return new Span(
            data: $spanData,
            register: $this->register,
            generateSpanId: $this->generateSpanId,
            environment: $this->environment,
        );
    }

    /**
     * Create a child generation on this trace.
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

        return new Generation(
            data: $spanData,
        );
    }
}
