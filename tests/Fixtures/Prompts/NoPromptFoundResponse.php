<?php

declare(strict_types=1);

namespace Tests\Fixtures\Prompts;

use GuzzleHttp\Psr7\Response;

class NoPromptFoundResponse extends Response
{
    public function __construct(int $status = 404, array $headers = [], string $version = '1.1', ?string $reason = null)
    {
        parent::__construct($status, $headers, (string) json_encode($this->payload()), $version, $reason);
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'message' => "Prompt not found: 'foo' with label 'production'",
            'error' => 'LangfuseNotFoundError',
        ];
    }
}
