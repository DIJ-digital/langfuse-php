<?php

declare(strict_types=1);

use DIJ\Langfuse\PHP\Langfuse;
use DIJ\Langfuse\PHP\Responses\MediaResponse;
use DIJ\Langfuse\PHP\Responses\MediaUploadUrlResponse;
use DIJ\Langfuse\PHP\Testing\Responses\GetMediaResponse;
use DIJ\Langfuse\PHP\Testing\Responses\PatchMediaResponse;
use DIJ\Langfuse\PHP\Testing\Responses\PostMediaUploadUrlResponse;
use DIJ\Langfuse\PHP\Transporters\HttpTransporter;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;

it('can get a media record by id', function (): void {
    $mock = new MockHandler([new GetMediaResponse]);
    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $media = (new Langfuse(new HttpTransporter($client)))
        ->media()
        ->get('media-abc123');

    expect($media)->toBeInstanceOf(MediaResponse::class)
        ->and($media->mediaId)->toBe('media-abc123')
        ->and($media->contentType)->toBe('image/png')
        ->and($media->contentLength)->toBe(12345)
        ->and($media->uploadedAt)->toBe('2025-01-22T10:00:00.000Z')
        ->and($media->url)->toBe('https://storage.langfuse.com/media/abc123.png')
        ->and($media->urlExpiry)->toBe('2025-01-22T11:00:00.000Z');
});

it('can get upload url', function (): void {
    $mock = new MockHandler([new PostMediaUploadUrlResponse]);
    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $response = (new Langfuse(new HttpTransporter($client)))
        ->media()
        ->getUploadUrl(
            traceId: 'trace-123',
            contentType: 'image/png',
            contentLength: 12345,
            sha256Hash: 'abc123hash',
            field: 'input',
        );

    expect($response)->toBeInstanceOf(MediaUploadUrlResponse::class)
        ->and($response->mediaId)->toBe('media-new123')
        ->and($response->uploadUrl)->toBe('https://storage.langfuse.com/upload/presigned-url');
});

it('can patch a media record', function (): void {
    $mock = new MockHandler([new PatchMediaResponse]);
    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $langfuse = new Langfuse(new HttpTransporter($client));
    $langfuse->media()->patch(
        mediaId: 'media-abc123',
        uploadedAt: '2025-01-22T10:00:00.000Z',
        uploadHttpStatus: 200,
    );

    expect($mock->count())->toBe(0);
});
