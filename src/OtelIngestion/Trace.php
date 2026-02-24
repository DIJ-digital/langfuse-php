<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\OtelIngestion;

use DIJ\Langfuse\PHP\OtelIngestion;

class Trace
{
    public string $id {
        get => $this->traceId;
    }

    public function __construct(
        public readonly string $traceId,
        public readonly string $rootSpanId,
        private readonly OtelIngestion $ingestion,
    ) {}

    /**
     * Update this trace by sending an additional root span with updated attributes.
     *
     * @param  array<string, mixed>|string|null  $input
     * @param  array<string, mixed>|string|null  $output
     * @param  array<string, mixed>|null  $metadata
     * @param  list<string>|null  $tags
     */
    public function update(
        ?string $name = null,
        ?string $userId = null,
        ?string $sessionId = null,
        array|string|null $input = null,
        array|string|null $output = null,
        ?array $metadata = null,
        ?array $tags = null,
    ): self {
        $attributes = $this->ingestion->buildObservationAttributes(
            type: 'span',
            input: $input,
            output: $output,
            metadata: $metadata,
        );

        // Add trace-level attributes for fields that are trace-specific
        if ($userId !== null) {
            $attributes[] = \DIJ\Langfuse\PHP\Otel\OtelAttribute::string('langfuse.user.id', $userId);
        }

        if ($sessionId !== null) {
            $attributes[] = \DIJ\Langfuse\PHP\Otel\OtelAttribute::string('langfuse.session.id', $sessionId);
        }

        if ($tags !== null) {
            $attributes[] = \DIJ\Langfuse\PHP\Otel\OtelAttribute::stringArray('langfuse.trace.tags', $tags);
        }

        $span = OtelIngestion::buildSpan(
            traceId: $this->traceId,
            spanId: $this->rootSpanId,
            name: $name ?? 'update',
            startTimeUnixNano: OtelIngestion::nowNano(),
            attributes: $attributes,
        );

        $this->ingestion->send([$span]);

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
        ?string $spanId = null,
        array|string|null $input = null,
        array|string|null $output = null,
        ?string $startTime = null,
        ?string $endTime = null,
        ?array $metadata = null,
    ): Span {
        return $this->ingestion->span(
            traceId: $this->traceId,
            parentSpanId: $this->rootSpanId,
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
     * Create a child generation on this trace.
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
            parentSpanId: $this->rootSpanId,
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
