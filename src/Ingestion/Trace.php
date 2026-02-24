<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Ingestion;

use DIJ\Langfuse\PHP\Ingestion;

class Trace
{
    public readonly string $id;

    public function __construct(
        public readonly string $traceId,
        private readonly Ingestion $ingestion,
    ) {
        $this->id = $this->traceId;
    }

    /**
     * Update this trace.
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
        $body = array_filter([
            'id' => $this->traceId,
            'name' => $name,
            'userId' => $userId,
            'sessionId' => $sessionId,
            'input' => $input,
            'output' => $output,
            'metadata' => $metadata,
            'tags' => $tags,
        ], static fn (mixed $v): bool => $v !== null);

        $this->ingestion->send('trace-create', $body);

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
