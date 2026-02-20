<?php

declare(strict_types=1);

use DIJ\Langfuse\PHP\Langfuse;
use DIJ\Langfuse\PHP\Responses\CommentListResponse;
use DIJ\Langfuse\PHP\Responses\CommentResponse;
use DIJ\Langfuse\PHP\Testing\Responses\GetCommentListResponse;
use DIJ\Langfuse\PHP\Testing\Responses\GetCommentResponse;
use DIJ\Langfuse\PHP\Testing\Responses\PostCommentResponse;
use DIJ\Langfuse\PHP\Transporters\HttpTransporter;
use DIJ\Langfuse\PHP\ValueObjects\MetaData;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;

it('can get a comment by id', function (): void {
    $mock = new MockHandler([
        new GetCommentResponse(),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $comment = (new Langfuse(new HttpTransporter($client)))
        ->comment()
        ->get('comment-abc123');

    expect($comment)->toBeInstanceOf(CommentResponse::class)
        ->and($comment->id)->toBe('comment-abc123')
        ->and($comment->content)->toBe('This is a test comment')
        ->and($comment->objectType)->toBe('trace')
        ->and($comment->objectId)->toBe('trace-123')
        ->and($comment->authorUserId)->toBe('user-456')
        ->and($comment->projectId)->toBe('proj-abc123');
});

it('can list comments', function (): void {
    $mock = new MockHandler([
        new GetCommentListResponse(),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $comments = (new Langfuse(new HttpTransporter($client)))
        ->comment()
        ->list();

    expect($comments)->toBeInstanceOf(CommentListResponse::class)
        ->and($comments->data)->toBeArray()
        ->and($comments->data)->toHaveCount(2)
        ->and($comments->data[0])->toBeInstanceOf(CommentResponse::class)
        ->and($comments->data[0]->content)->toBe('First comment')
        ->and($comments->data[0]->objectType)->toBe('trace')
        ->and($comments->data[1]->content)->toBe('Second comment')
        ->and($comments->data[1]->objectType)->toBe('observation')
        ->and($comments->data[1]->authorUserId)->toBeNull()
        ->and($comments->meta)->toBeInstanceOf(MetaData::class)
        ->and($comments->meta->totalItems)->toBe(2);
});

it('can create a comment', function (): void {
    $mock = new MockHandler([
        new PostCommentResponse(),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $comment = (new Langfuse(new HttpTransporter($client)))
        ->comment()
        ->create(
            content: 'New comment content',
            objectType: 'trace',
            objectId: 'trace-123',
        );

    expect($comment)->toBeInstanceOf(CommentResponse::class)
        ->and($comment->id)->toBe('comment-new123')
        ->and($comment->content)->toBe('New comment content')
        ->and($comment->objectType)->toBe('trace')
        ->and($comment->objectId)->toBe('trace-123');
});

it('can list comments with filters', function (): void {
    $mock = new MockHandler([
        new GetCommentListResponse(),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $comments = (new Langfuse(new HttpTransporter($client)))
        ->comment()
        ->list(
            page: 1,
            limit: 10,
            objectType: 'trace',
            objectId: 'trace-123',
            authorUserId: 'user-456',
        );

    expect($comments)->toBeInstanceOf(CommentListResponse::class)
        ->and($comments->data)->toHaveCount(2);
});
