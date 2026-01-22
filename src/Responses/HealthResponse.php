<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Responses;

readonly class HealthResponse
{
    public function __construct(
        public string $status,
    ) {}

    /**
     * @param  array{status: string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            status: $data['status'],
        );
    }
}
