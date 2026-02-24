<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\OtelIngestion;

use DIJ\Langfuse\PHP\OtelIngestion;

class Generation
{
    public readonly string $id;

    public function __construct(
        public readonly string $generationId,
        public readonly string $traceId,
        public readonly string $parentSpanId,
        private readonly OtelIngestion $ingestion,
    ) {
        $this->id = $this->generationId;
    }

    /**
     * Update this generation by sending a new span event with updated attributes.
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
        ?string $endTime = null,
    ): self {
        $attributes = $this->ingestion->buildObservationAttributes(
            type: 'generation',
            input: $input,
            output: $output,
            model: $model,
            modelParameters: $modelParameters,
            metadata: $metadata,
        );

        $span = OtelIngestion::buildSpan(
            traceId: $this->traceId,
            spanId: $this->generationId,
            parentSpanId: $this->parentSpanId,
            name: 'generation-update',
            startTimeUnixNano: OtelIngestion::nowNano(),
            endTimeUnixNano: $endTime !== null ? OtelIngestion::isoToNano($endTime) : null,
            attributes: $attributes,
        );

        $this->ingestion->send([$span]);

        return $this;
    }
}
