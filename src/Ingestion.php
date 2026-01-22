<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP;

use DIJ\Langfuse\PHP\Contracts\TransporterInterface;
use DIJ\Langfuse\PHP\Ingestion\Generation;
use DIJ\Langfuse\PHP\Ingestion\Span;
use DIJ\Langfuse\PHP\Ingestion\Trace;

class Ingestion
{
    private const string INGESTION_ENDPOINT = '/api/public/ingestion';

    public function __construct(
        private readonly TransporterInterface $transporter,
        private readonly string $environment = 'default',
    ) {}

    public static function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0F | 0x40);
        $bytes[8] = chr(ord($bytes[8]) & 0x3F | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', mb_str_split(bin2hex($bytes), 4));
    }

    public static function now(): string
    {
        return gmdate('Y-m-d\TH:i:s.').sprintf('%03d', (int) (microtime(true) * 1000) % 1000).'Z';
    }

    /**
     * Create a trace.
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
    ): Trace {
        $traceId ??= self::uuid();

        $body = array_filter([
            'id' => $traceId,
            'timestamp' => self::now(),
            'name' => $name,
            'userId' => $userId,
            'sessionId' => $sessionId,
            'input' => $input,
            'output' => $output,
            'metadata' => $metadata,
            'tags' => $tags,
            'environment' => $this->environment,
        ], static fn (mixed $v): bool => $v !== null);

        $this->send('trace-create', $body);

        return new Trace(
            traceId: $traceId,
            ingestion: $this,
        );
    }

    /**
     * Create a span.
     *
     * @param  array<string, mixed>|string|null  $input
     * @param  array<string, mixed>|string|null  $output
     * @param  array<string, mixed>|null  $metadata
     */
    public function span(
        string $traceId,
        string $name,
        ?string $spanId = null,
        ?string $parentObservationId = null,
        array|string|null $input = null,
        array|string|null $output = null,
        ?string $startTime = null,
        ?string $endTime = null,
        ?array $metadata = null,
    ): Span {
        $spanId ??= self::uuid();

        $body = array_filter([
            'id' => $spanId,
            'traceId' => $traceId,
            'parentObservationId' => $parentObservationId,
            'name' => $name,
            'startTime' => $startTime ?? self::now(),
            'endTime' => $endTime,
            'input' => $input,
            'output' => $output,
            'metadata' => $metadata,
            'environment' => $this->environment,
        ], static fn (mixed $v): bool => $v !== null);

        $this->send('span-create', $body);

        return new Span(
            spanId: $spanId,
            traceId: $traceId,
            ingestion: $this,
        );
    }

    /**
     * Create a generation.
     *
     * @param  array<string, mixed>|string  $input
     * @param  array<string, mixed>|string  $output
     * @param  array<string, mixed>|null  $modelParameters
     * @param  array<string, mixed>|null  $metadata
     */
    public function generation(
        string $traceId,
        string $name,
        array|string $input,
        array|string $output,
        ?string $generationId = null,
        ?string $parentObservationId = null,
        ?string $model = null,
        ?array $modelParameters = null,
        ?string $promptName = null,
        ?int $promptVersion = null,
        ?array $metadata = null,
        ?string $startTime = null,
        ?string $endTime = null,
    ): Generation {
        $generationId ??= self::uuid();

        $body = array_filter([
            'id' => $generationId,
            'traceId' => $traceId,
            'parentObservationId' => $parentObservationId,
            'name' => $name,
            'startTime' => $startTime ?? self::now(),
            'endTime' => $endTime,
            'input' => $input,
            'output' => $output,
            'model' => $model,
            'modelParameters' => $modelParameters,
            'promptName' => $promptName,
            'promptVersion' => $promptVersion,
            'metadata' => $metadata,
            'environment' => $this->environment,
        ], static fn (mixed $v): bool => $v !== null);

        $this->send('generation-create', $body);

        return new Generation(
            generationId: $generationId,
            traceId: $traceId,
            ingestion: $this,
        );
    }

    /**
     * Send a single ingestion event to the Langfuse API.
     *
     * @param  array<string, mixed>  $body
     */
    public function send(string $type, array $body): void
    {
        $this->transporter->postJson(self::INGESTION_ENDPOINT, [
            'batch' => [
                [
                    'id' => self::uuid(),
                    'timestamp' => self::now(),
                    'type' => $type,
                    'body' => $body,
                ],
            ],
        ]);
    }
}
