<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\OtelIngestion;

use DIJ\Langfuse\PHP\OtelIngestion;

class Span
{
    public string $id {
        get => $this->spanId;
    }

    public function __construct(
        public readonly string $spanId,
        public readonly string $traceId,
        public readonly string $parentSpanId,
        private readonly OtelIngestion $ingestion,
    ) {}

    /**
     * Update this span by sending a new span event with the same spanId.
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
        $attributes = $this->ingestion->buildObservationAttributes(
            type: 'span',
            input: $input,
            output: $output,
            metadata: $metadata,
        );

        $span = OtelIngestion::buildSpan(
            traceId: $this->traceId,
            spanId: $this->spanId,
            parentSpanId: $this->parentSpanId,
            name: $name ?? 'update',
            startTimeUnixNano: OtelIngestion::nowNano(),
            endTimeUnixNano: $endTime !== null ? OtelIngestion::isoToNano($endTime) : null,
            attributes: $attributes,
        );

        $this->ingestion->send([$span]);

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
        ?string $spanId = null,
        array|string|null $input = null,
        array|string|null $output = null,
        ?string $startTime = null,
        ?string $endTime = null,
        ?array $metadata = null,
    ): self {
        return $this->ingestion->span(
            traceId: $this->traceId,
            parentSpanId: $this->spanId,
            name: $name,
            spanId: $spanId,
            input: $input,
            output: $output,
            startTime: $startTime,
            endTime: $endTime,
            metadata: $metadata,
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
        string $name,
        array|string $input,
        array|string $output,
        ?string $generationId = null,
        ?string $model = null,
        ?array $modelParameters = null,
        ?string $promptName = null,
        ?int $promptVersion = null,
        ?array $metadata = null,
        ?string $startTime = null,
        ?string $endTime = null,
    ): Generation {
        return $this->ingestion->generation(
            traceId: $this->traceId,
            parentSpanId: $this->spanId,
            name: $name,
            input: $input,
            output: $output,
            generationId: $generationId,
            model: $model,
            modelParameters: $modelParameters,
            promptName: $promptName,
            promptVersion: $promptVersion,
            metadata: $metadata,
            startTime: $startTime,
            endTime: $endTime,
        );
    }
}
