<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP;

use DIJ\Langfuse\PHP\Contracts\TransporterInterface;
use DIJ\Langfuse\PHP\Enums\SpanKind;
use DIJ\Langfuse\PHP\Ingestion\Generation;
use DIJ\Langfuse\PHP\Ingestion\Span;
use DIJ\Langfuse\PHP\Ingestion\Trace;
use DIJ\Langfuse\PHP\Otlp\Serializer;
use DIJ\Langfuse\PHP\Otlp\SpanData;

class Ingestion
{
    private const SDK_VERSION = '1.0.0';

    /** @var array<string, SpanData> */
    private array $spans = [];

    private readonly Serializer $serializer;

    public function __construct(
        private readonly TransporterInterface $transporter,
        private readonly string $environment = 'default',
        private readonly string $serviceName = '',
    ) {
        $this->serializer = new Serializer;
    }

    /**
     * Create a trace (root span). Returns a Trace handle for updates and child creation.
     *
     * @param  array<string, mixed>|string|null  $input
     * @param  array<string, mixed>|string|null  $output
     * @param  array<string, mixed>|null  $metadata
     * @param  array<int, string>|null  $tags
     */
    public function trace(
        string $name,
        ?string $traceId = null,
        ?string $sessionId = null,
        ?string $userId = null,
        array|string|null $input = null,
        array|string|null $output = null,
        ?array $metadata = null,
        ?array $tags = null,
    ): Trace {
        $traceId = $traceId ?? self::generateTraceId();
        $spanId = self::generateSpanId();

        $spanData = new SpanData(
            traceId: $traceId,
            spanId: $spanId,
            parentSpanId: null,
            name: $name,
            kind: SpanKind::TRACE,
            startTimeNano: Serializer::nowNano(),
        );

        $attrs = array_filter([
            'langfuse.trace.name' => $name,
            'langfuse.environment' => $this->environment,
            'user.id' => $userId,
            'session.id' => $sessionId,
            'langfuse.trace.input' => Serializer::serializeValue($input),
            'langfuse.trace.output' => Serializer::serializeValue($output),
            'langfuse.trace.tags' => $tags,
        ], static fn (mixed $v): bool => $v !== null);

        if ($metadata !== null) {
            $attrs = array_merge($attrs, Serializer::flattenMetadata('langfuse.trace.metadata', $metadata));
        }

        $spanData->setAttributes($attrs);
        $this->register($spanData);

        return new Trace(
            data: $spanData,
            register: $this->register(...),
            generateSpanId: self::generateSpanId(...),
            environment: $this->environment,
        );
    }

    /**
     * Create a span directly (for manual ID management).
     *
     * @param  array<string, mixed>|string|null  $input
     * @param  array<string, mixed>|string|null  $output
     * @param  array<string, mixed>|null  $metadata
     */
    public function span(
        string $traceId,
        string $name,
        ?string $parentObservationId = null,
        array|string|null $input = null,
        array|string|null $output = null,
        ?string $startTime = null,
        ?string $endTime = null,
        ?array $metadata = null,
        ?string $spanId = null,
    ): Span {
        $spanId = $spanId ?? self::generateSpanId();
        $startNano = $startTime !== null ? Serializer::toNanoTimestamp($startTime) : Serializer::nowNano();

        $spanData = new SpanData(
            traceId: $traceId,
            spanId: $spanId,
            parentSpanId: $parentObservationId,
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
        $this->register($spanData);

        return new Span(
            data: $spanData,
            register: $this->register(...),
            generateSpanId: self::generateSpanId(...),
            environment: $this->environment,
        );
    }

    /**
     * Create a generation directly (for manual ID management).
     *
     * @param  array<string, mixed>|string  $input
     * @param  array<string, mixed>|string  $output
     * @param  array<string, mixed>|null  $modelParameters
     * @param  array<string, mixed>|null  $metadata
     */
    public function generation(
        array|string $input,
        array|string $output,
        string $traceId,
        string $name,
        ?string $parentObservationId = null,
        ?string $model = null,
        ?array $modelParameters = null,
        ?string $promptName = null,
        ?int $promptVersion = null,
        ?array $metadata = null,
        ?string $generationId = null,
    ): Generation {
        $generationId = $generationId ?? self::generateSpanId();

        $spanData = new SpanData(
            traceId: $traceId,
            spanId: $generationId,
            parentSpanId: $parentObservationId,
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
        $this->register($spanData);

        return new Generation(data: $spanData);
    }

    /**
     * Flush all buffered spans to the Langfuse OTLP endpoint.
     */
    public function flush(): void
    {
        if ($this->spans === []) {
            return;
        }

        $payload = $this->serializer->serialize(
            $this->spans,
            self::SDK_VERSION,
            $this->serviceName,
        );

        $this->transporter->postJson(
            '/api/public/otel/v1/traces',
            $payload,
        );

        $this->spans = [];
    }

    public function __destruct()
    {
        try {
            $this->flush();
        } catch (\Throwable) {
            // Silently discard — destructors must not throw (PHP 8+).
        }
    }

    /**
     * @return array<string, SpanData>
     *
     * @internal Exposed for testing only.
     */
    public function getSpans(): array
    {
        return $this->spans;
    }

    private function register(SpanData $span): void
    {
        $this->spans[$span->spanId] = $span;
    }

    private static function generateTraceId(): string
    {
        return bin2hex(random_bytes(16));
    }

    private static function generateSpanId(): string
    {
        return bin2hex(random_bytes(8));
    }
}
