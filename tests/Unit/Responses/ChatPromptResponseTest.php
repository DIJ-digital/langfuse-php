<?php

declare(strict_types=1);

use DIJ\Langfuse\PHP\Responses\ChatPromptResponse;

it('converts isActive string to boolean in chat prompt response', function (): void {
    // Test data with isActive as string (simulating API response bug)
    $data = [
        'id' => 'test-id',
        'name' => 'test-prompt',
        'prompt' => [['role' => 'user', 'content' => 'Hello']],
        'type' => 'chat',
        'config' => [],
        'tags' => [],
        'projectId' => 'test-project',
        'createdBy' => 'test-user',
        'createdAt' => '2024-01-01T00:00:00Z',
        'updatedAt' => '2024-01-01T00:00:00Z',
        'version' => 1,
        'labels' => [],
        'isActive' => 'true', // String instead of boolean
        'commitMessage' => null,
        'resolutionGraph' => null,
    ];

    $response = ChatPromptResponse::fromArray($data);

    expect($response->isActive)->toBeTrue();
    expect($response->isActive)->toBeBool();
});

it('converts false string to boolean in chat prompt response', function (): void {
    $data = [
        'id' => 'test-id',
        'name' => 'test-prompt',
        'prompt' => [['role' => 'user', 'content' => 'Hello']],
        'type' => 'chat',
        'config' => [],
        'tags' => [],
        'projectId' => 'test-project',
        'createdBy' => 'test-user',
        'createdAt' => '2024-01-01T00:00:00Z',
        'updatedAt' => '2024-01-01T00:00:00Z',
        'version' => 1,
        'labels' => [],
        'isActive' => 'false', // String instead of boolean
        'commitMessage' => null,
        'resolutionGraph' => null,
    ];

    $response = ChatPromptResponse::fromArray($data);

    expect($response->isActive)->toBeFalse();
    expect($response->isActive)->toBeBool();
});

it('handles null isActive correctly in chat prompt response', function (): void {
    $data = [
        'id' => 'test-id',
        'name' => 'test-prompt',
        'prompt' => [['role' => 'user', 'content' => 'Hello']],
        'type' => 'chat',
        'config' => [],
        'tags' => [],
        'projectId' => 'test-project',
        'createdBy' => 'test-user',
        'createdAt' => '2024-01-01T00:00:00Z',
        'updatedAt' => '2024-01-01T00:00:00Z',
        'version' => 1,
        'labels' => [],
        'isActive' => null,
        'commitMessage' => null,
        'resolutionGraph' => null,
    ];

    $response = ChatPromptResponse::fromArray($data);

    expect($response->isActive)->toBeNull();
});

it('handles missing isActive field in chat prompt response', function (): void {
    $data = [
        'id' => 'test-id',
        'name' => 'test-prompt',
        'prompt' => [['role' => 'user', 'content' => 'Hello']],
        'type' => 'chat',
        'config' => [],
        'tags' => [],
        'projectId' => 'test-project',
        'createdBy' => 'test-user',
        'createdAt' => '2024-01-01T00:00:00Z',
        'updatedAt' => '2024-01-01T00:00:00Z',
        'version' => 1,
        'labels' => [],
        // isActive is missing
        'commitMessage' => null,
        'resolutionGraph' => null,
    ];

    $response = ChatPromptResponse::fromArray($data);

    expect($response->isActive)->toBeNull();
});
