<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP;

use DIJ\Langfuse\PHP\Contracts\TransporterInterface;
use DIJ\Langfuse\PHP\Responses\MediaResponse;
use DIJ\Langfuse\PHP\Responses\MediaUploadUrlResponse;
use JsonException;

class Media
{
    public function __construct(private readonly TransporterInterface $transporter) {}

    /**
     * @throws JsonException
     */
    public function get(string $mediaId): MediaResponse
    {
        $response = $this->transporter->get(
            uri: sprintf('/api/public/media/%s', urlencode($mediaId)),
        );

        /** @var array{
         *     mediaId: string,
         *     contentType: string,
         *     contentLength: int,
         *     uploadedAt: string,
         *     url: string,
         *     urlExpiry: string
         * } $data
         */
        $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        return MediaResponse::fromArray($data);
    }

    /**
     * @throws JsonException
     */
    public function getUploadUrl(
        string $traceId,
        string $contentType,
        int $contentLength,
        string $sha256Hash,
        string $field,
        ?string $observationId = null,
    ): MediaUploadUrlResponse {
        $response = $this->transporter->postJson(
            uri: '/api/public/media',
            data: array_filter([
                'traceId' => $traceId,
                'observationId' => $observationId,
                'contentType' => $contentType,
                'contentLength' => $contentLength,
                'sha256Hash' => $sha256Hash,
                'field' => $field,
            ], fn ($v) => $v !== null),
        );

        /** @var array{
         *     uploadUrl: string|null,
         *     mediaId: string
         * } $data
         */
        $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        return MediaUploadUrlResponse::fromArray($data);
    }

    public function patch(
        string $mediaId,
        string $uploadedAt,
        int $uploadHttpStatus,
        ?string $uploadHttpError = null,
        ?int $uploadTimeMs = null,
    ): void {
        $this->transporter->patchJson(
            uri: sprintf('/api/public/media/%s', urlencode($mediaId)),
            data: array_filter([
                'uploadedAt' => $uploadedAt,
                'uploadHttpStatus' => $uploadHttpStatus,
                'uploadHttpError' => $uploadHttpError,
                'uploadTimeMs' => $uploadTimeMs,
            ], fn ($v) => $v !== null),
        );
    }
}
