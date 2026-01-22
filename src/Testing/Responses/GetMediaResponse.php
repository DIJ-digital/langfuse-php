<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Testing\Responses;

use GuzzleHttp\Psr7\Response;

class GetMediaResponse extends Response
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
    public function payload(array $data = []): array
    {
        return array_merge([
            'mediaId' => 'media-abc123',
            'contentType' => 'image/png',
            'contentLength' => 12345,
            'uploadedAt' => '2025-01-22T10:00:00.000Z',
            'url' => 'https://storage.langfuse.com/media/abc123.png',
            'urlExpiry' => '2025-01-22T11:00:00.000Z',
        ], $data);
    }
}
