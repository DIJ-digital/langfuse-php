<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Responses;

class IngestionResponse
{
    /**
     * @param  array<int, array{id: string, status: int}>  $successes
     * @param  array<int, array{id: string, status: int, error: string}>  $errors
     */
    public function __construct(
        public readonly array $successes,
        public readonly array $errors,
    ) {}

    /**
     * @param  array{successes: array<int, array{id: string, status: int}>, errors: array<int, array{id: string, status: int, error: string}>}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            successes: $data['successes'] ?? [],
            errors: $data['errors'] ?? [],
        );
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    public function getSuccessCount(): int
    {
        return count($this->successes);
    }

    public function getErrorCount(): int
    {
        return count($this->errors);
    }
}
