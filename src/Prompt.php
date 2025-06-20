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
        } catch (NotFoundException $e) {
            return null;
        }

        return PromptResponse::fromArray($response);
    }

    /**
     * Get all prompts
     */
    public function list(): PromptListResponse
    {
        $response = $this->transporter->get('/api/public/v2/prompts');

        return PromptListResponse::fromArray($response);
    }
}
