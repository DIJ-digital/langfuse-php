<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP;

use DIJ\Langfuse\PHP\Contracts\TransporterInterface;
use DIJ\Langfuse\PHP\Responses\ObservationListResponse;
use DIJ\Langfuse\PHP\Responses\ObservationResponse;
use JsonException;

class Observation
{
    public function __construct(private readonly TransporterInterface $transporter) {}

    /**
     * @throws JsonException
     */
    public function get(string $observationId): ObservationResponse
    {
        $response = $this->transporter->get(
            uri: sprintf('/api/public/observations/%s', urlencode($observationId)),
        );

        /** @var array{
         *     id: string,
         *     traceId: string|null,
         *     type: string,
         *     name: string|null,
         *     startTime: string,
         *     endTime: string|null,
         *     completionStartTime: string|null,
         *     model: string|null,
         *     modelParameters: array<string, mixed>|null,
         *     input: mixed,
         *     output: mixed,
         *     metadata: mixed,
         *     version: string|null,
         *     parentObservationId: string|null,
         *     level: string|null,
         *     statusMessage: string|null,
         *     promptId: string|null,
         *     promptName: string|null,
         *     promptVersion: int|null,
         *     usage: array{input: int|null, output: int|null, total: int|null, unit: string|null, inputCost: float|null, outputCost: float|null, totalCost: float|null}|null,
         *     calculatedInputCost: float|null,
         *     calculatedOutputCost: float|null,
         *     calculatedTotalCost: float|null,
         *     latency: float|null,
         *     timeToFirstToken: float|null,
         *     projectId: string,
         *     createdAt: string,
         *     updatedAt: string,
         *     environment: string|null
         * } $data
         */
        $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        return ObservationResponse::fromArray($data);
    }

    /**
     * @throws JsonException
     */
    public function list(
        ?int $page = null,
        ?int $limit = null,
        ?string $name = null,
        ?string $userId = null,
        ?string $traceId = null,
        ?string $parentObservationId = null,
        ?string $type = null,
        ?string $fromStartTime = null,
        ?string $toStartTime = null,
        ?string $version = null,
        ?string $environment = null,
    ): ObservationListResponse {
        $response = $this->transporter->get(
            uri: '/api/public/observations',
            options: ['query' => array_filter([
                'page' => $page,
                'limit' => $limit,
                'name' => $name,
                'userId' => $userId,
                'traceId' => $traceId,
                'parentObservationId' => $parentObservationId,
                'type' => $type,
                'fromStartTime' => $fromStartTime,
                'toStartTime' => $toStartTime,
                'version' => $version,
                'environment' => $environment,
            ])]
        );

        /** @var array{
         *     data: array<int, array{
         *         id: string,
         *         traceId: string|null,
         *         type: string,
         *         name: string|null,
         *         startTime: string,
         *         endTime: string|null,
         *         completionStartTime: string|null,
         *         model: string|null,
         *         modelParameters: array<string, mixed>|null,
         *         input: mixed,
         *         output: mixed,
         *         metadata: mixed,
         *         version: string|null,
         *         parentObservationId: string|null,
         *         level: string|null,
         *         statusMessage: string|null,
         *         promptId: string|null,
         *         promptName: string|null,
         *         promptVersion: int|null,
         *         usage: array{input: int|null, output: int|null, total: int|null, unit: string|null, inputCost: float|null, outputCost: float|null, totalCost: float|null}|null,
         *         calculatedInputCost: float|null,
         *         calculatedOutputCost: float|null,
         *         calculatedTotalCost: float|null,
         *         latency: float|null,
         *         timeToFirstToken: float|null,
         *         projectId: string,
         *         createdAt: string,
         *         updatedAt: string,
         *         environment: string|null
         *     }>,
         *     meta: array{page: int, limit: int, totalPages: int, totalItems: int}
         * } $data
         */
        $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        return ObservationListResponse::fromArray($data);
    }
}
