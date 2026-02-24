<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP;

use DIJ\Langfuse\PHP\Contracts\TransporterInterface;
use DIJ\Langfuse\PHP\Otel\OtelAttribute;
use DIJ\Langfuse\PHP\OtelIngestion\Generation;
use DIJ\Langfuse\PHP\OtelIngestion\Span;
use DIJ\Langfuse\PHP\OtelIngestion\Trace;

/**
 * Ingestion client that posts spans via Langfuse's OTLP/HTTP JSON endpoint.
 *
 * Uses POST /api/public/otel/v1/traces with ExportTraceServiceRequest JSON.
 */
class OtelIngestion
{
    private const string OTEL_ENDPOINT = '/api/public/otel/v1/traces';

    private const string SCOPE_NAME = 'langfuse-php';

    public function __construct(
        private readonly TransporterInterface $transporter,
        private readonly string $environment = 'default',
        private readonly string $version = '',
    ) {}

    /**
     * Generate a 128-bit trace ID (32 hex characters).
     */
    public static function traceId(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Generate a 64-bit span ID (16 hex characters).
     */
    public static function spanId(): string
    {
        return bin2hex(random_bytes(8));
    }

    /**
     * Get the current time as nanoseconds since Unix epoch (string).
     */
    public static function nowNano(): string
    {
        $microtime = microtime(true);

        return sprintf('%d', (int) ($microtime * 1_000_000_000));
    }

    /**
     * Create a trace (root span).
     *
     * @param  array<string, mixed>|string|null  $input
     * @param  array<string, mixed>|string|null  $output
     * @param  array<string, mixed>|null  $metadata
     * @param  list<string>|null  $tags
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
        ?bool $public = null,
        ?string $release = null,
    ): Trace {
        $traceId ??= self::traceId();
        $rootSpanId = self::spanId();
        $now = self::nowNano();

        $attributes = $this->buildTraceAttributes(
            name: $name,
            sessionId: $sessionId,
            userId: $userId,
            input: $input,
            output: $output,
            metadata: $metadata,
            tags: $tags,
            public: $public,
            release: $release,
        );

        $span = self::buildSpan(
            traceId: $traceId,
            spanId: $rootSpanId,
            name: $name,
            startTimeUnixNano: $now,
            attributes: $attributes,
        );

        $this->send([$span]);

        return new Trace(
            traceId: $traceId,
            rootSpanId: $rootSpanId,
            ingestion: $this,
        );
    }

    /**
     * Create a span (child observation).
     *
     * @param  array<string, mixed>|string|null  $input
     * @param  array<string, mixed>|string|null  $output
     * @param  array<string, mixed>|null  $metadata
     */
    public function span(
        string $traceId,
        string $parentSpanId,
        string $name,
        ?string $spanId = null,
        array|string|null $input = null,
        array|string|null $output = null,
        ?string $startTime = null,
        ?string $endTime = null,
        ?array $metadata = null,
    ): Span {
        $spanId ??= self::spanId();

        $attributes = $this->buildObservationAttributes(
            type: 'span',
            input: $input,
            output: $output,
            metadata: $metadata,
        );

        $span = self::buildSpan(
            traceId: $traceId,
            spanId: $spanId,
            parentSpanId: $parentSpanId,
            name: $name,
            startTimeUnixNano: $startTime !== null ? self::isoToNano($startTime) : self::nowNano(),
            endTimeUnixNano: $endTime !== null ? self::isoToNano($endTime) : null,
            attributes: $attributes,
        );

        $this->send([$span]);

        return new Span(
            spanId: $spanId,
            traceId: $traceId,
            parentSpanId: $parentSpanId,
            ingestion: $this,
        );
    }

    /**
     * Create a generation (child observation with model info).
     *
     * @param  array<string, mixed>|string  $input
     * @param  array<string, mixed>|string  $output
     * @param  array<string, mixed>|null  $modelParameters
     * @param  array<string, mixed>|null  $metadata
     */
    public function generation(
        string $traceId,
        string $parentSpanId,
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
        $generationId ??= self::spanId();

        $attributes = $this->buildObservationAttributes(
            type: 'generation',
            input: $input,
            output: $output,
            model: $model,
            modelParameters: $modelParameters,
            promptName: $promptName,
            promptVersion: $promptVersion,
            metadata: $metadata,
        );

        $span = self::buildSpan(
            traceId: $traceId,
            spanId: $generationId,
            parentSpanId: $parentSpanId,
            name: $name,
            startTimeUnixNano: $startTime !== null ? self::isoToNano($startTime) : self::nowNano(),
            endTimeUnixNano: $endTime !== null ? self::isoToNano($endTime) : null,
            attributes: $attributes,
        );

        $this->send([$span]);

        return new Generation(
            generationId: $generationId,
            traceId: $traceId,
            parentSpanId: $parentSpanId,
            ingestion: $this,
        );
    }

    /**
     * Send one or more OTLP spans to the Langfuse OTel endpoint.
     *
     * @param  list<array<string, mixed>>  $spans
     */
    public function send(array $spans): void
    {
        $this->transporter->postJson(self::OTEL_ENDPOINT, [
            'resourceSpans' => [
                [
                    'resource' => [
                        'attributes' => [],
                    ],
                    'scopeSpans' => [
                        [
                            'scope' => [
                                'name' => self::SCOPE_NAME,
                            ],
                            'spans' => $spans,
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Build an OTLP span array.
     *
     * @param  list<array{key: string, value: array<string, mixed>}>  $attributes
     * @return array<string, mixed>
     */
    public static function buildSpan(
        string $traceId,
        string $spanId,
        string $name,
        string $startTimeUnixNano,
        ?string $parentSpanId = null,
        ?string $endTimeUnixNano = null,
        array $attributes = [],
        int $kind = 1,
    ): array {
        return array_filter([
            'traceId' => $traceId,
            'spanId' => $spanId,
            'parentSpanId' => $parentSpanId,
            'name' => $name,
            'kind' => $kind,
            'startTimeUnixNano' => $startTimeUnixNano,
            'endTimeUnixNano' => $endTimeUnixNano,
            'attributes' => $attributes,
            'status' => ['code' => 1],
        ], static fn (mixed $v): bool => $v !== null);
    }

    /**
     * Convert an ISO 8601 timestamp to nanoseconds since epoch.
     */
    public static function isoToNano(string $iso): string
    {
        $timestamp = strtotime($iso);

        if ($timestamp === false) {
            return self::nowNano();
        }

        return sprintf('%d', $timestamp * 1_000_000_000);
    }

    /**
     * Build trace-level langfuse.* attributes.
     *
     * @param  array<string, mixed>|string|null  $input
     * @param  array<string, mixed>|string|null  $output
     * @param  array<string, mixed>|null  $metadata
     * @param  list<string>|null  $tags
     * @return list<array{key: string, value: array<string, mixed>}>
     */
    private function buildTraceAttributes(
        string $name,
        ?string $sessionId,
        ?string $userId,
        array|string|null $input,
        array|string|null $output,
        ?array $metadata,
        ?array $tags,
        ?bool $public,
        ?string $release,
    ): array {
        $attrs = [];

        $attrs[] = OtelAttribute::string('langfuse.trace.name', $name);

        if ($sessionId !== null) {
            $attrs[] = OtelAttribute::string('langfuse.session.id', $sessionId);
        }

        if ($userId !== null) {
            $attrs[] = OtelAttribute::string('langfuse.user.id', $userId);
        }

        if ($input !== null) {
            $attrs[] = OtelAttribute::string('langfuse.trace.input', is_array($input) ? json_encode($input, JSON_THROW_ON_ERROR) : $input);
        }

        if ($output !== null) {
            $attrs[] = OtelAttribute::string('langfuse.trace.output', is_array($output) ? json_encode($output, JSON_THROW_ON_ERROR) : $output);
        }

        if ($tags !== null) {
            $attrs[] = OtelAttribute::stringArray('langfuse.trace.tags', $tags);
        }

        if ($public !== null) {
            $attrs[] = OtelAttribute::bool('langfuse.trace.public', $public);
        }

        if ($release !== null) {
            $attrs[] = OtelAttribute::string('langfuse.release', $release);
        }

        if ($this->environment !== '') {
            $attrs[] = OtelAttribute::string('langfuse.environment', $this->environment);
        }

        if ($this->version !== '') {
            $attrs[] = OtelAttribute::string('langfuse.version', $this->version);
        }

        if ($metadata !== null) {
            foreach ($metadata as $key => $value) {
                $attrs[] = OtelAttribute::string(
                    'langfuse.trace.metadata.'.$key,
                    is_string($value) ? $value : json_encode($value, JSON_THROW_ON_ERROR),
                );
            }
        }

        return $attrs;
    }

    /**
     * Build observation-level langfuse.* attributes.
     *
     * @param  array<string, mixed>|string|null  $input
     * @param  array<string, mixed>|string|null  $output
     * @param  array<string, mixed>|null  $modelParameters
     * @param  array<string, mixed>|null  $metadata
     * @return list<array{key: string, value: array<string, mixed>}>
     */
    public function buildObservationAttributes(
        string $type = 'span',
        array|string|null $input = null,
        array|string|null $output = null,
        ?string $model = null,
        ?array $modelParameters = null,
        ?string $promptName = null,
        ?int $promptVersion = null,
        ?array $metadata = null,
    ): array {
        $attrs = [];

        $attrs[] = OtelAttribute::string('langfuse.observation.type', $type);

        if ($input !== null) {
            $attrs[] = OtelAttribute::string('langfuse.observation.input', is_array($input) ? json_encode($input, JSON_THROW_ON_ERROR) : $input);
        }

        if ($output !== null) {
            $attrs[] = OtelAttribute::string('langfuse.observation.output', is_array($output) ? json_encode($output, JSON_THROW_ON_ERROR) : $output);
        }

        if ($model !== null) {
            $attrs[] = OtelAttribute::string('langfuse.observation.model.name', $model);
        }

        if ($modelParameters !== null) {
            $attrs[] = OtelAttribute::string('langfuse.observation.model.parameters', json_encode($modelParameters, JSON_THROW_ON_ERROR));
        }

        if ($promptName !== null) {
            $attrs[] = OtelAttribute::string('langfuse.observation.prompt.name', $promptName);
        }

        if ($promptVersion !== null) {
            $attrs[] = OtelAttribute::int('langfuse.observation.prompt.version', $promptVersion);
        }

        if ($this->environment !== '') {
            $attrs[] = OtelAttribute::string('langfuse.environment', $this->environment);
        }

        if ($this->version !== '') {
            $attrs[] = OtelAttribute::string('langfuse.version', $this->version);
        }

        if ($metadata !== null) {
            foreach ($metadata as $key => $value) {
                $attrs[] = OtelAttribute::string(
                    'langfuse.observation.metadata.'.$key,
                    is_string($value) ? $value : json_encode($value, JSON_THROW_ON_ERROR),
                );
            }
        }

        return $attrs;
    }
}
