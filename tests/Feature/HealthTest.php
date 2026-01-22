<?php

declare(strict_types=1);

use DIJ\Langfuse\PHP\Langfuse;
use DIJ\Langfuse\PHP\Responses\HealthResponse;
use DIJ\Langfuse\PHP\Testing\Responses\GetHealthResponse;
use DIJ\Langfuse\PHP\Transporters\HttpTransporter;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;

it('can check health status', function (): void {
    $mock = new MockHandler([new GetHealthResponse]);
    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $health = (new Langfuse(new HttpTransporter($client)))
        ->health()
        ->check();

    expect($health)->toBeInstanceOf(HealthResponse::class)
        ->and($health->status)->toBe('OK');
});
