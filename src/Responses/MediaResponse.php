<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Responses;

readonly class MediaResponse
{
    public function __construct(
        public string $mediaId,
        public string $contentType,
        public int $contentLength,
        public string $uploadedAt,
        public string $url,
        public string $urlExpiry,
    ) {}

    /**
     * @param array{
     *     mediaId: string,
     *     contentType: string,
     *     contentLength: int,
     *     uploadedAt: string,
     *     url: string,
     *     urlExpiry: string
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            mediaId: $data['mediaId'],
            contentType: $data['contentType'],
            contentLength: $data['contentLength'],
            uploadedAt: $data['uploadedAt'],
            url: $data['url'],
            urlExpiry: $data['urlExpiry'],
        );
    }
}
