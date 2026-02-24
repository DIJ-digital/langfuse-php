<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Ingestion;

use DIJ\Langfuse\PHP\Ingestion;

class Generation
{
    public readonly string $id;

    public function __construct(
        public readonly string $generationId,
        public readonly string $traceId,
        private readonly Ingestion $ingestion,
    ) {
        $this->id = $this->generationId;
    }

    /**
     * Update this generation.
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
        $body = array_filter([
            'id' => $this->generationId,
            'traceId' => $this->traceId,
            'input' => $input,
            'output' => $output,
            'model' => $model,
            'modelParameters' => $modelParameters,
            'metadata' => $metadata,
            'endTime' => $endTime,
        ], static fn (mixed $v): bool => $v !== null);

        $this->ingestion->send('generation-update', $body);

        return $this;
    }
}
