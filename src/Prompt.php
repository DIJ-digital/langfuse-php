<?php

declare(strict_types=1);

namespace DIJ\Langfuse;

use DIJ\Langfuse\Contracts\TransporterInterface;
use DIJ\Langfuse\Exceptions\NotFoundException;
use DIJ\Langfuse\Responses\PromptListResponse;
use DIJ\Langfuse\Responses\PromptResponse;

class Prompt
{
    public function __construct(private readonly TransporterInterface $transporter) {}

    public function text(string $promptName, ?string $version = null, ?string $label = null): ?PromptResponse
    {
        try {
            $response = $this->transporter->get(
                uri: sprintf('/api/public/v2/prompts/%s', $promptName),
                data: array_filter([
                    'version' => $version,
                    'label' => $label,
                ])
            );
        } catch (NotFoundException) {
            return null;
        }

        return PromptResponse::fromArray($response);
    }

    public function list(
        ?string $name,
        ?string $version,
        ?string $label,
        ?string $tag,
        ?int $page,
        ?string $fromUpdatedAt,
        ?string $toUpdatedAt
    ): PromptListResponse {
        $response = $this->transporter->get(
            uri: '/api/public/v2/prompts',
            data: array_filter([
                'name' => $name,
                'version' => $version,
                'label' => $label,
                'tag' => $tag,
                'page' => $page,
                'fromUpdatedAt' => $fromUpdatedAt,
                'toUpdatedAt' => $toUpdatedAt,
            ])
        );

        return PromptListResponse::fromArray($response);
    }
}
