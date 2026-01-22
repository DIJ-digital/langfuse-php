<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Responses;

readonly class ObservationResponse
{
    /**
     * @param  array<string, mixed>|null  $modelParameters
     * @param  array{input: int|null, output: int|null, total: int|null, unit: string|null, inputCost: float|null, outputCost: float|null, totalCost: float|null}|null  $usage
     */
    public function __construct(
        public string $id,
        public ?string $traceId,
        public string $type,
        public ?string $name,
        public string $startTime,
        public ?string $endTime,
        public ?string $completionStartTime,
        public ?string $model,
        public ?array $modelParameters,
        public mixed $input,
        public mixed $output,
        public mixed $metadata,
        public ?string $version,
        public ?string $parentObservationId,
        public ?string $level,
        public ?string $statusMessage,
        public ?string $promptId,
        public ?string $promptName,
        public ?int $promptVersion,
        public ?array $usage,
        public ?float $calculatedInputCost,
        public ?float $calculatedOutputCost,
        public ?float $calculatedTotalCost,
        public ?float $latency,
        public ?float $timeToFirstToken,
        public string $projectId,
        public string $createdAt,
        public string $updatedAt,
        public ?string $environment = null,
    ) {}

    /**
     * @param array{
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
     *     usage?: array{input: int|null, output: int|null, total: int|null, unit: string|null, inputCost: float|null, outputCost: float|null, totalCost: float|null}|null,
     *     calculatedInputCost?: float|null,
     *     calculatedOutputCost?: float|null,
     *     calculatedTotalCost?: float|null,
     *     latency?: float|null,
     *     timeToFirstToken?: float|null,
     *     projectId: string,
     *     createdAt: string,
     *     updatedAt: string,
     *     environment?: string|null
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            traceId: $data['traceId'],
            type: $data['type'],
            name: $data['name'],
            startTime: $data['startTime'],
            endTime: $data['endTime'],
            completionStartTime: $data['completionStartTime'],
            model: $data['model'],
            modelParameters: $data['modelParameters'],
            input: $data['input'],
            output: $data['output'],
            metadata: $data['metadata'],
            version: $data['version'],
            parentObservationId: $data['parentObservationId'],
            level: $data['level'],
            statusMessage: $data['statusMessage'],
            promptId: $data['promptId'],
            promptName: $data['promptName'],
            promptVersion: $data['promptVersion'],
            usage: $data['usage'] ?? null,
            calculatedInputCost: $data['calculatedInputCost'] ?? null,
            calculatedOutputCost: $data['calculatedOutputCost'] ?? null,
            calculatedTotalCost: $data['calculatedTotalCost'] ?? null,
            latency: $data['latency'] ?? null,
            timeToFirstToken: $data['timeToFirstToken'] ?? null,
            projectId: $data['projectId'],
            createdAt: $data['createdAt'],
            updatedAt: $data['updatedAt'],
            environment: $data['environment'] ?? null,
        );
    }
}
