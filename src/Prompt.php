<?php

declare(strict_types=1);

namespace DIJ\Langfuse;

use DIJ\Langfuse\Contracts\TransporterInterface;
use DIJ\Langfuse\Exceptions\NotFoundException;
use DIJ\Langfuse\Responses\PromptListResponse;
use DIJ\Langfuse\Responses\PromptResponse;
use JsonException;

class Prompt
{
    public function __construct(private readonly TransporterInterface $transporter) {}

    /**
     * @throws JsonException
     */
    public function text(string $promptName, ?string $version = null, ?string $label = null): ?PromptResponse
    {
        try {
            $response = $this->transporter->get(
                uri: sprintf('/api/public/v2/prompts/%s', $promptName),
                options: array_filter([
                    'version' => $version,
                    'label' => $label,
                ])
            );

            /** @var array{
             * id: string,
             * name: string,
             * prompt: string,
             * type: string,
             * config: array<int, string>,
             * tags: array<int, string>,
             * projectId: string,
             * createdBy: string,
             * createdAt: string,
             * updatedAt: string,
             * version: int,
             * labels: array<int,string>,
             * isActive: string|null,
             * commitMessage: string|null,
             * resolutionGraph: string|null,
             * } $data */
            $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);
        } catch (NotFoundException) {
            return null;
        }

        return PromptResponse::fromArray($data);
    }

    public function list(?string $name = null, ?string $version = null, ?string $label = null, ?string $tag = null, ?int $page = null, ?string $fromUpdatedAt = null, ?string $toUpdatedAt = null): PromptListResponse
    {
        $response = $this->transporter->get(
            uri: '/api/public/v2/prompts',
            options: array_filter([
                'name' => $name,
                'version' => $version,
                'label' => $label,
                'tag' => $tag,
                'page' => $page,
                'fromUpdatedAt' => $fromUpdatedAt,
                'toUpdatedAt' => $toUpdatedAt,
            ])
        );

        /** @var array{
         * data: array<int, array{name: string, tags: array<int, string>, lastUpdatedAt: string, versions: array<int, int>, labels: array<int, string>, lastConfig: array<string, mixed>}>,
         * meta: array{page: int, limit: int, totalPages: int, totalItems: int},
         * pagination: array{page: int, limit: int, totalPages: int, totalItems: int}
         * } $data */
        $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        return PromptListResponse::fromArray($data);
    }
}
