<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP;

use DIJ\Langfuse\PHP\Contracts\TransporterInterface;
use DIJ\Langfuse\PHP\Responses\IngestionResponse;
use DIJ\Langfuse\PHP\ValueObjects\IngestionEvent;
use JsonException;

class Ingestion
{
    public function __construct(private readonly TransporterInterface $transporter) {}

    /**
     * @param  array<int, IngestionEvent>  $events
     * @param  array<string, mixed>|null  $metadata
     *
     * @throws JsonException
     */
    public function batch(array $events, ?array $metadata = null): IngestionResponse
    {
        $batch = array_map(fn (IngestionEvent $event): array => $event->toArray(), $events);

        $data = ['batch' => $batch];

        if ($metadata !== null) {
            $data['metadata'] = $metadata;
        }

        $response = $this->transporter->postJson('/api/public/ingestion', $data);

        /** @var array{successes: array<int, array{id: string, status: int}>, errors: array<int, array{id: string, status: int, error: string}>} $responseData */
        $responseData = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        return IngestionResponse::fromArray($responseData);
    }

    /**
     * Create a trace event
     *
     * @param  array<string, mixed>  $body
     * @param  array<string, mixed>|null  $metadata
     *
     * @throws JsonException
     */
    public function trace(string $id, array $body, ?string $timestamp = null, ?array $metadata = null): IngestionResponse
    {
        $event = new IngestionEvent($id, Enums\EventType::TRACE_CREATE, $body, $timestamp, $metadata);

        return $this->batch([$event], $metadata);
    }

    /**
     * Create a score event
     *
     * @param  array<string, mixed>  $body
     * @param  array<string, mixed>|null  $metadata
     *
     * @throws JsonException
     */
    public function score(string $id, array $body, ?string $timestamp = null, ?array $metadata = null): IngestionResponse
    {
        $event = new IngestionEvent($id, Enums\EventType::SCORE_CREATE, $body, $timestamp, $metadata);

        return $this->batch([$event], $metadata);
    }

    /**
     * Create a span event
     *
     * @param  array<string, mixed>  $body
     * @param  array<string, mixed>|null  $metadata
     *
     * @throws JsonException
     */
    public function span(string $id, array $body, ?string $timestamp = null, ?array $metadata = null): IngestionResponse
    {
        $event = new IngestionEvent($id, Enums\EventType::SPAN_CREATE, $body, $timestamp, $metadata);

        return $this->batch([$event], $metadata);
    }

    /**
     * Update a span event
     *
     * @param  array<string, mixed>  $body
     * @param  array<string, mixed>|null  $metadata
     *
     * @throws JsonException
     */
    public function spanUpdate(string $id, array $body, ?string $timestamp = null, ?array $metadata = null): IngestionResponse
    {
        $event = new IngestionEvent($id, Enums\EventType::SPAN_UPDATE, $body, $timestamp, $metadata);

        return $this->batch([$event], $metadata);
    }

    /**
     * Create a generation event
     *
     * @param  array<string, mixed>  $body
     * @param  array<string, mixed>|null  $metadata
     *
     * @throws JsonException
     */
    public function generation(string $id, array $body, ?string $timestamp = null, ?array $metadata = null): IngestionResponse
    {
        $event = new IngestionEvent($id, Enums\EventType::GENERATION_CREATE, $body, $timestamp, $metadata);

        return $this->batch([$event], $metadata);
    }

    /**
     * Update a generation event
     *
     * @param  array<string, mixed>  $body
     * @param  array<string, mixed>|null  $metadata
     *
     * @throws JsonException
     */
    public function generationUpdate(string $id, array $body, ?string $timestamp = null, ?array $metadata = null): IngestionResponse
    {
        $event = new IngestionEvent($id, Enums\EventType::GENERATION_UPDATE, $body, $timestamp, $metadata);

        return $this->batch([$event], $metadata);
    }

    /**
     * Create an event
     *
     * @param  array<string, mixed>  $body
     * @param  array<string, mixed>|null  $metadata
     *
     * @throws JsonException
     */
    public function event(string $id, array $body, ?string $timestamp = null, ?array $metadata = null): IngestionResponse
    {
        $event = new IngestionEvent($id, Enums\EventType::EVENT_CREATE, $body, $timestamp, $metadata);

        return $this->batch([$event], $metadata);
    }

    /**
     * Create an SDK log event
     *
     * @param  array<string, mixed>  $body
     * @param  array<string, mixed>|null  $metadata
     *
     * @throws JsonException
     */
    public function sdkLog(string $id, array $body, ?string $timestamp = null, ?array $metadata = null): IngestionResponse
    {
        $event = new IngestionEvent($id, Enums\EventType::SDK_LOG, $body, $timestamp, $metadata);

        return $this->batch([$event], $metadata);
    }

    /**
     * Create an observation event
     *
     * @param  array<string, mixed>  $body
     * @param  array<string, mixed>|null  $metadata
     *
     * @throws JsonException
     */
    public function observation(string $id, array $body, ?string $timestamp = null, ?array $metadata = null): IngestionResponse
    {
        $event = new IngestionEvent($id, Enums\EventType::OBSERVATION_CREATE, $body, $timestamp, $metadata);

        return $this->batch([$event], $metadata);
    }
}
