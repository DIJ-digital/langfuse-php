<?php

declare(strict_types=1);

namespace Tests\Fixtures\Prompts;

use GuzzleHttp\Psr7\Response;

class GetPromptResponse extends Response
{
    public function __construct(int $status = 200, array $headers = [], string $version = '1.1', ?string $reason = null, array $data = [])
    {
        parent::__construct($status, $headers, (string) json_encode($this->payload($data)), $version, $reason);
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(array $data): array
    {
        return array_merge([
            'id' => 'b522b473-25b0-4c6e-a918-72606da402d3',
            'createdAt' => '2025-05-28T06:48:35.156Z',
            'updatedAt' => '2025-05-28T06:48:35.156Z',
            'projectId' => 'cmb6akern01ppad08i2effff',
            'createdBy' => 'cm2eq9k5x026dxgx5pgohffff',
            'prompt' => 'You are a research bot',
            'name' => 'general_instructions',
            'version' => 1,
            'type' => 'text',
            'isActive' => null,
            'config' => [],
            'tags' => [],
            'labels' => [
                'production',
                'latest',
            ],
            'commitMessage' => null,
            'resolutionGraph' => null,
        ], $data);
    }
}
