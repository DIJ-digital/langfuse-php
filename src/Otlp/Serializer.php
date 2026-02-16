<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Otlp;

class Serializer
{
    /**
     * Serialize buffered spans into an OTLP JSON payload.
     *
     * @param  array<string, SpanData>  $spans
     * @return array<string, mixed>
     */
    public function serialize(array $spans, string $sdkVersion, string $serviceName = ''): array
    {
        return [
            'resourceSpans' => [
                [
                    'resource' => [
                        'attributes' => [
                            self::attribute('service.name', $serviceName),
                            self::attribute('service.version', $sdkVersion),
                        ],
                    ],
                    'scopeSpans' => [
                        [
                            'scope' => [
                                'name' => 'langfuse-sdk',
                                'version' => $sdkVersion,
                            ],
                            'spans' => array_values(array_map(
                                fn (SpanData $span): array => $this->buildSpan($span),
                                $spans,
                            )),
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSpan(SpanData $span): array
    {
        return array_filter([
            'traceId' => $span->traceId,
            'spanId' => $span->spanId,
            'parentSpanId' => $span->parentSpanId,
            'name' => $span->name,
            'kind' => 1, // INTERNAL
            'startTimeUnixNano' => $span->startTimeNano,
            'endTimeUnixNano' => $span->getEndTimeNano() ?? $span->startTimeNano,
            'attributes' => $this->buildAttributes($span),
            'status' => (object) [],
        ], static fn (mixed $v): bool => $v !== null);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildAttributes(SpanData $span): array
    {
        $attrs = [];

        foreach ($span->getAttributes() as $key => $value) {
            if ($value === null) {
                continue;
            }

            $attrs[] = self::attribute($key, $value);
        }

        return $attrs;
    }

    /**
     * Build an OTLP attribute from a key-value pair.
     *
     * @return array{key: string, value: array<string, mixed>}
     */
    public static function attribute(string $key, mixed $value): array
    {
        return [
            'key' => $key,
            'value' => self::attributeValue($value),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function attributeValue(mixed $value): array
    {
        if (is_string($value)) {
            return ['stringValue' => $value];
        }

        if (is_int($value)) {
            return ['intValue' => (string) $value];
        }

        if (is_float($value)) {
            return ['doubleValue' => $value];
        }

        if (is_bool($value)) {
            return ['boolValue' => $value];
        }

        if (is_array($value)) {
            // Check if it's a list of strings (for tags)
            if (array_is_list($value) && array_reduce($value, static fn (bool $carry, mixed $v): bool => $carry && is_string($v), true)) {
                /** @var list<string> $value */
                return [
                    'arrayValue' => [
                        'values' => array_map(
                            static fn (string $v): array => ['stringValue' => $v],
                            $value,
                        ),
                    ],
                ];
            }

            // Otherwise JSON-encode
            return ['stringValue' => json_encode($value, JSON_THROW_ON_ERROR)];
        }

        // Fallback: JSON-encode anything else
        return ['stringValue' => json_encode($value, JSON_THROW_ON_ERROR)];
    }

    /**
     * Convert an ISO-8601 date string to nanosecond Unix timestamp string.
     */
    public static function toNanoTimestamp(string $dateTime): string
    {
        $timestamp = strtotime($dateTime);

        if ($timestamp === false) {
            $timestamp = time();
        }

        return (string) ($timestamp * 1_000_000_000);
    }

    /**
     * Get the current time as a nanosecond Unix timestamp string.
     */
    public static function nowNano(): string
    {
        return (string) (int) (microtime(true) * 1_000_000_000);
    }

    /**
     * Serialize a value for use as an OTLP string attribute.
     * Strings pass through; everything else gets JSON-encoded.
     */
    public static function serializeValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    /**
     * Flatten a metadata array into individual OTLP attributes.
     *
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed> Flattened key-value pairs with prefixed keys
     */
    public static function flattenMetadata(string $prefix, array $metadata): array
    {
        $result = [];

        foreach ($metadata as $key => $value) {
            if (is_string($value) || is_int($value)) {
                $result["{$prefix}.{$key}"] = $value;
            } else {
                $result["{$prefix}.{$key}"] = json_encode($value, JSON_THROW_ON_ERROR);
            }
        }

        return $result;
    }
}
