<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Responses;

readonly class MediaUploadUrlResponse
{
    public function __construct(
        public string $mediaId,
        public ?string $uploadUrl,
    ) {}

    /**
     * @param array{
     *     uploadUrl: string|null,
     *     mediaId: string
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            mediaId: $data['mediaId'],
            uploadUrl: $data['uploadUrl'],
        );
    }
}
