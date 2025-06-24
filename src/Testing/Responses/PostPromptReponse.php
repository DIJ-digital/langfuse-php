<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Testing\Responses;

use GuzzleHttp\Psr7\Response;

class PostPromptReponse extends Response
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(int $status = 201, array $headers = [], string $version = '1.1', ?string $reason = null, array $data = [])
    {
        parent::__construct($status, $headers, (string) json_encode($this->payload($data)), $version, $reason);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function payload(array $data = []): array
    {
        return array_merge([
            'id' => '22969be5-5115-41f7-aeb4-15656922950c',
            'createdAt' => '2025-06-23T13:30:25.449Z',
            'updatedAt' => '2025-06-23T13:30:25.449Z',
            'projectId' => 'cmb6akern01ppad08i2e0c3dm',
            'createdBy' => 'API',
            'prompt' => 'example',
            'name' => 'test',
            'version' => 1,
            'type' => 'text',
            'isActive' => null,
            'config' => [
                'foo' => 'bar',
            ],
            'tags' => [
                'example',
            ],
            'labels' => [
                'example',
                'latest',
            ],
            'commitMessage' => 'example',
        ], $data);
    }
}
