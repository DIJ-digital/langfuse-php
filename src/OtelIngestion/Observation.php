<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\OtelIngestion;

use DIJ\Langfuse\PHP\Enums\ObservationLevel;
use DIJ\Langfuse\PHP\OtelIngestion;
use InvalidArgumentException;

abstract class Observation
{
    public readonly string $startTimeUnixNano;

    private ?string $endTimeUnixNano = null;

    /** @var array<string, bool|int|string|list<string>> */
    protected array $attributes = [];

    private ?ObservationLevel $level = null;

    private ?string $statusMessage = null;

    /**
     * @param array<string, bool|int|string|list<string>> $traceAttributes
     */
    public function __construct(
        protected readonly OtelIngestion $ingestion,
        public readonly string $traceId,
        public readonly string $spanId,
        public readonly ?string $parentSpanId,
        protected string $name,
        ?string $startTime = null,
        protected array $traceAttributes = [],
    ) {
        if (preg_match('/^(?!0{32})[0-9a-f]{32}$/', $traceId) !== 1) {
            throw new InvalidArgumentException("Trace ID [{$traceId}] must be 32 lowercase hex characters and not all zero.");
        }

        foreach (array_filter([$spanId, $parentSpanId]) as $id) {
            if (preg_match('/^(?!0{16})[0-9a-f]{16}$/', $id) !== 1) {
                throw new InvalidArgumentException("Span ID [{$id}] must be 16 lowercase hex characters and not all zero.");
            }
        }

        $this->startTimeUnixNano = $startTime !== null ? OtelIngestion::isoToNano($startTime) : OtelIngestion::nowNano();
    }

    /**
     * @param array<string, mixed>|string|null $input
     * @param array<string, mixed>|string|null $output
     * @param array<string, mixed>|null $metadata
     */
    public function span(
        string $name,
        ?string $spanId = null,
        ?string $startTime = null,
        array|string|null $input = null,
        array|string|null $output = null,
        ?array $metadata = null,
    ): Span {
        return (new Span(
            ingestion: $this->ingestion,
            traceId: $this->traceId,
            spanId: $spanId ?? OtelIngestion::spanId(),
            parentSpanId: $this->spanId,
            name: $name,
            startTime: $startTime,
            traceAttributes: $this->traceAttributes,
        ))->update(input: $input, output: $output, metadata: $metadata);
    }

    /**
     * @param array<string, mixed>|string|null $input
     * @param array<string, mixed>|string|null $output
     * @param array<string, mixed>|null $modelParameters
     * @param array<string, int>|null $usageDetails
     * @param array<string, mixed>|null $metadata
     */
    public function generation(
        string $name,
        ?string $generationId = null,
        ?string $startTime = null,
        array|string|null $input = null,
        array|string|null $output = null,
        ?string $model = null,
        ?array $modelParameters = null,
        ?array $usageDetails = null,
        ?string $promptName = null,
        ?int $promptVersion = null,
        ?array $metadata = null,
    ): Generation {
        return (new Generation(
            ingestion: $this->ingestion,
            traceId: $this->traceId,
            spanId: $generationId ?? OtelIngestion::spanId(),
            parentSpanId: $this->spanId,
            name: $name,
            startTime: $startTime,
            traceAttributes: $this->traceAttributes,
        ))->update(
            input: $input,
            output: $output,
            model: $model,
            modelParameters: $modelParameters,
            usageDetails: $usageDetails,
            promptName: $promptName,
            promptVersion: $promptVersion,
            metadata: $metadata,
        );
    }

    public function end(?string $endTime = null): void
    {
        if ($this->endTimeUnixNano !== null) {
            return;
        }

        $this->endTimeUnixNano = $endTime !== null ? OtelIngestion::isoToNano($endTime) : OtelIngestion::nowNano();

        $this->ingestion->export($this->toOtlp());
    }

    /**
     * @return array<string, mixed>
     */
    public function toOtlp(): array
    {
        $attributes = [];

        foreach ([...$this->traceAttributes, ...$this->attributes] as $key => $value) {
            $attributes[] = ['key' => $key, 'value' => $this->anyValue($value)];
        }

        return array_filter([
            'traceId' => $this->traceId,
            'spanId' => $this->spanId,
            'parentSpanId' => $this->parentSpanId,
            'name' => $this->name,
            'kind' => 1,
            'startTimeUnixNano' => $this->startTimeUnixNano,
            'endTimeUnixNano' => $this->endTimeUnixNano ?? $this->startTimeUnixNano,
            'attributes' => $attributes,
            'status' => $this->level === ObservationLevel::ERROR ? array_filter(['code' => 2, 'message' => $this->statusMessage]) : null,
        ], static fn (mixed $value): bool => $value !== null);
    }

    /**
     * @param array<string, mixed>|string|null $input
     * @param array<string, mixed>|string|null $output
     * @param array<string, mixed>|null $metadata
     */
    protected function updateObservation(
        ?string $name,
        array|string|null $input,
        array|string|null $output,
        ?array $metadata,
        ?ObservationLevel $level,
        ?string $statusMessage,
    ): void {
        $this->name = $name ?? $this->name;
        $this->level = $level ?? $this->level;
        $this->statusMessage = $statusMessage ?? $this->statusMessage;

        $this->set('langfuse.observation.input', $input);
        $this->set('langfuse.observation.output', $output);
        $this->set('langfuse.observation.level', $level?->value);
        $this->set('langfuse.observation.status_message', $statusMessage);

        foreach ($metadata ?? [] as $key => $value) {
            $this->set('langfuse.observation.metadata.' . $key, $value);
        }
    }

    protected function set(string $key, mixed $value): void
    {
        if ($value !== null) {
            $this->attributes[$key] = $this->scalar($value);
        }
    }

    protected function setTraceAttribute(string $key, mixed $value): void
    {
        if ($value !== null) {
            $this->traceAttributes[$key] = $this->scalar($value);
        }
    }

    private function scalar(mixed $value): bool|int|string
    {
        if (is_bool($value) || is_int($value) || is_string($value)) {
            return $value;
        }

        return json_encode($value, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
    }

    /**
     * @param bool|int|string|list<string> $value
     * @return array<string, mixed>
     */
    private function anyValue(bool|int|string|array $value): array
    {
        return match (true) {
            is_bool($value) => ['boolValue' => $value],
            is_int($value) => ['intValue' => (string) $value],
            is_string($value) => ['stringValue' => $value],
            default => ['arrayValue' => ['values' => array_map(static fn (string $item): array => ['stringValue' => $item], $value)]],
        };
    }
}
