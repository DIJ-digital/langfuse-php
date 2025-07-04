<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Testing\Responses;

class IngestionResponse
{
    public static function success(): array
    {
        return [
            'successes' => [
                ['id' => 'test-123', 'status' => 201],
            ],
            'errors' => [],
        ];
    }

    public static function withErrors(): array
    {
        return [
            'successes' => [
                ['id' => 'test-123', 'status' => 201],
            ],
            'errors' => [
                ['id' => 'test-456', 'status' => 400, 'error' => 'Invalid event data'],
            ],
        ];
    }
}
