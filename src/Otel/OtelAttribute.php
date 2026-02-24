<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Otel;

/**
 * Factory for OTLP key-value attribute arrays.
 */
final class OtelAttribute
{
    /**
     * Create a string attribute.
     *
     * @return array{key: string, value: array{stringValue: string}}
     */
    public static function string(string $key, string $value): array
    {
        return ['key' => $key, 'value' => ['stringValue' => $value]];
    }

    /**
     * Create a boolean attribute.
     *
     * @return array{key: string, value: array{boolValue: bool}}
     */
    public static function bool(string $key, bool $value): array
    {
        return ['key' => $key, 'value' => ['boolValue' => $value]];
    }

    /**
     * Create an integer attribute.
     *
     * @return array{key: string, value: array{intValue: string}}
     */
    public static function int(string $key, int $value): array
    {
        return ['key' => $key, 'value' => ['intValue' => (string) $value]];
    }

    /**
     * Create a string array attribute.
     *
     * @param  list<string>  $values
     * @return array{key: string, value: array{arrayValue: array{values: list<array{stringValue: string}>}}}
     */
    public static function stringArray(string $key, array $values): array
    {
        return [
            'key' => $key,
            'value' => [
                'arrayValue' => [
                    'values' => array_map(
                        static fn (string $v): array => ['stringValue' => $v],
                        $values,
                    ),
                ],
            ],
        ];
    }
}
