<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP;

use DateTimeImmutable;
use DIJ\Langfuse\PHP\Contracts\TransporterInterface;
use DIJ\Langfuse\PHP\OtelIngestion\Generation;
use DIJ\Langfuse\PHP\OtelIngestion\Span;
use DIJ\Langfuse\PHP\OtelIngestion\Trace;

class OtelIngestion
{
    private const string ENDPOINT = '/api/public/otel/v1/traces';

    private const string SCOPE_NAME = 'langfuse-php';

    public function __construct(
        private readonly TransporterInterface $transporter,
        private readonly string $environment = 'default',
        private readonly string $version = '',
        private readonly string $serviceName = 'unknown_service:php',
    ) {
    }

    public static function traceId(?string $seed = null): string
    {
        return $seed === null ? bin2hex(random_bytes(16)) : md5($seed);
    }

    public static function spanId(?string $seed = null): string
    {
        return $seed === null ? bin2hex(random_bytes(8)) : substr(md5($seed), 0, 16);
    }

    public static function nowNano(): string
    {
        return (new DateTimeImmutable())->format('Uu') . '000';
    }

    public static function isoToNano(string $iso): string
    {
        return (new DateTimeImmutable($iso))->format('Uu') . '000';
    }

    /**
     * @param array<string, mixed>|string|null $input
     * @param array<string, mixed>|string|null $output
     * @param array<string, mixed>|null $metadata
     * @param list<string>|null $tags
     */
    public function trace(
        string $name,
        ?string $traceId = null,
        ?string $spanId = null,
        ?string $startTime = null,
        ?string $sessionId = null,
        ?string $userId = null,
        array|string|null $input = null,
        array|string|null $output = null,
        ?array $metadata = null,
        ?array $tags = null,
        ?bool $public = null,
        ?string $release = null,
    ): Trace {
        return (new Trace(
            ingestion: $this,
            traceId: $traceId ?? self::traceId(),
            spanId: $spanId ?? self::spanId(),
            parentSpanId: null,
            name: $name,
            startTime: $startTime,
            traceAttributes: $this->commonAttributes(),
        ))->update(
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
    }

    /**
     * @param array<string, mixed>|string|null $input
     * @param array<string, mixed>|string|null $output
     * @param array<string, mixed>|null $metadata
     */
    public function span(
        string $traceId,
        string $parentSpanId,
        string $name,
        ?string $spanId = null,
        ?string $startTime = null,
        array|string|null $input = null,
        array|string|null $output = null,
        ?array $metadata = null,
    ): Span {
        return (new Span(
            ingestion: $this,
            traceId: $traceId,
            spanId: $spanId ?? self::spanId(),
            parentSpanId: $parentSpanId,
            name: $name,
            startTime: $startTime,
            traceAttributes: $this->commonAttributes(),
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
        string $traceId,
        string $parentSpanId,
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
            ingestion: $this,
            traceId: $traceId,
            spanId: $generationId ?? self::spanId(),
            parentSpanId: $parentSpanId,
            name: $name,
            startTime: $startTime,
            traceAttributes: $this->commonAttributes(),
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

    /**
     * @param array<string, mixed> $span
     */
    public function export(array $span): void
    {
        $this->transporter->postJson(self::ENDPOINT, [
            'resourceSpans' => [
                [
                    'resource' => [
                        'attributes' => [
                            ['key' => 'service.name', 'value' => ['stringValue' => $this->serviceName]],
                        ],
                    ],
                    'scopeSpans' => [
                        [
                            'scope' => ['name' => self::SCOPE_NAME],
                            'spans' => [$span],
                        ],
                    ],
                ],
            ],
        ], [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-langfuse-ingestion-version' => '4',
            ],
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function commonAttributes(): array
    {
        return array_filter([
            'langfuse.environment' => $this->environment,
            'langfuse.version' => $this->version,
        ], static fn (string $value): bool => $value !== '');
    }
}
