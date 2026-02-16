<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Otlp;

use DIJ\Langfuse\PHP\Enums\SpanKind;

class SpanData
{
    /** @var array<string, mixed> */
    private array $attributes = [];

    private ?string $endTimeNano = null;

    public function __construct(
        public readonly string $traceId,
        public readonly string $spanId,
        public readonly ?string $parentSpanId,
        public readonly string $name,
        public readonly SpanKind $kind,
        public readonly string $startTimeNano,
    ) {}

    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function setAttributes(array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            $this->attributes[$key] = $value;
        }
    }

    public function setEndTime(string $nanoTimestamp): void
    {
        $this->endTimeNano = $nanoTimestamp;
    }

    public function getEndTimeNano(): ?string
    {
        return $this->endTimeNano;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
