<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Testing\Responses;

use GuzzleHttp\Psr7\Response;

class GetChatPromptResponse extends Response
{
    /**
     * @param  array<array<string>|string>  $headers
     * @param  array<string, mixed>  $data
     */
    public function __construct(int $status = 200, array $headers = [], string $version = '1.1', ?string $reason = null, array $data = [])
    {
        parent::__construct($status, $headers, (string) json_encode($this->payload($data)), $version, $reason);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function payload(array $data): array
    {
        return array_merge([
            'id' => '4656f88f-0c5b-48b2-8941-d0acbdfabf08',
            'createdAt' => '2025-06-20T16:08:21.190Z',
            'updatedAt' => '2025-06-20T16:08:21.190Z',
            'projectId' => 'cmb6akern01ppad08i2e0ffff',
            'createdBy' => 'cm2eq9k5x026dxgx5pgohffff',
            'prompt' => [
                [
                    'role' => 'system',
                    'content' => 'Test {{name}}',
                ],
                [
                    'role' => 'user',
                    'content' => 'test {{user}}',
                ],
            ],
            'name' => 'chat_test',
            'version' => 1,
            'type' => 'chat',
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
