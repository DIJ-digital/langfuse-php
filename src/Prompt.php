<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP;

use DIJ\Langfuse\PHP\Contracts\TransporterInterface;
use DIJ\Langfuse\PHP\Enums\PromptType;
use DIJ\Langfuse\PHP\Exceptions\InvalidPromptTypeException;
use DIJ\Langfuse\PHP\Exceptions\NotFoundException;
use DIJ\Langfuse\PHP\Responses\ChatPromptResponse;
use DIJ\Langfuse\PHP\Responses\PromptListResponse;
use DIJ\Langfuse\PHP\Responses\TextPromptResponse;
use JsonException;

class Prompt
{
    public function __construct(private readonly TransporterInterface $transporter) {}

    /**
     * @throws JsonException
     * @throws InvalidPromptTypeException
     */
    public function text(string $promptName, ?string $version = null, ?string $label = null): ?TextPromptResponse
    {
        $data = $this->getPrompt($promptName, PromptType::TEXT, $version, $label);

        return $data !== null ? TextPromptResponse::fromArray($data) : null;
    }

    /**
     * @throws JsonException
     * @throws InvalidPromptTypeException
     */
    public function chat(string $promptName, ?string $version = null, ?string $label = null): ?ChatPromptResponse
    {
        $data = $this->getPrompt($promptName, PromptType::CHAT, $version, $label);

        return $data !== null ? ChatPromptResponse::fromArray($data) : null;
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

    /**
     * @return array{
     *  id: string,
     *  name: string,
     *  prompt: ($type is PromptType::TEXT ? string : array<int, array{role: string, content: string}>),
     *  type: string,
     *  config: array<int, string>,
     *  tags: array<int, string>,
     *  projectId: string,
     *  createdBy: string,
     *  createdAt: string,
     *  updatedAt: string,
     *  version: int,
     *  labels: array<int,string>,
     *  isActive: string|null,
     *  commitMessage: string|null,
     *  resolutionGraph: string|null,
     *  }|null
     *
     * @throws InvalidPromptTypeException
     * @throws JsonException
     */
    private function getPrompt(string $promptName, PromptType $type, ?string $version = null, ?string $label = null): ?array
    {
        try {
            $response = $this->transporter->get(
                uri: sprintf('/api/public/v2/prompts/%s', $promptName),
                options: array_filter([
                    'version' => $version,
                    'label' => $label,
                ])
            );
        } catch (NotFoundException) {
            return null;
        }

        /** @var array{
         * id: string,
         * name: string,
         * prompt: ($type is "text" ? string : array<int, array{role: string, content: string}>),
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
         * }  $data */
        $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        if ($data['type'] !== $type->value) {
            throw InvalidPromptTypeException::fromMessage("{$promptName} returns a prompt of type {$data['type']}, but expected {$type->value}.");
        }

        return $data;
    }

    /**
     * @param  ($type is PromptType::TEXT ? string : array<int, array{role: string, content: string}>)  $prompt  ,
     * @param  array<int, string>|null  $config
     * @param  array<int, string>|null  $tags
     * @return ($type is PromptType::TEXT ? TextPromptResponse : ChatPromptResponse)
     *
     * @throws JsonException
     */
    public function create(string $promptName, string|array $prompt, PromptType $type, ?string $label = null, ?array $config = null, ?array $tags = null, ?string $commitMessage = null): TextPromptResponse|ChatPromptResponse
    {
        $response = $this->transporter->postJson('/api/public/v2/prompts', [
            'name' => $promptName,
            'prompt' => $prompt,
            'type' => $type->value,
            'config' => $config,
            'tags' => $tags,
            'label' => $label,
            'commitMessage' => $commitMessage,
        ]);

        $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        if ($type === PromptType::TEXT) {
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
             * resolutionGraph: null,
             * } $data */
            return TextPromptResponse::fromArray($data);
        }

        /** @var array{
         * id: string,
         * name: string,
         * prompt: array<int, array{role: string, content: string}>,
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
         * resolutionGraph: null,
         * } $data */
        return ChatPromptResponse::fromArray($data);
    }
}
