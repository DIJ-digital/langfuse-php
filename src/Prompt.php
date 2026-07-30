<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP;

use DIJ\Langfuse\PHP\Contracts\TransporterInterface;
use DIJ\Langfuse\PHP\Enums\PromptType;
use DIJ\Langfuse\PHP\Exceptions\InvalidPromptTypeException;
use DIJ\Langfuse\PHP\Responses\ChatPromptResponse;
use DIJ\Langfuse\PHP\Responses\FallbackPrompt;
use DIJ\Langfuse\PHP\Responses\PromptListResponse;
use DIJ\Langfuse\PHP\Responses\TextPromptResponse;
use DIJ\Langfuse\PHP\ValueObjects\PromptListItem;
use Generator;
use JsonException;
use Throwable;

class Prompt
{
    public function __construct(
        private readonly TransporterInterface $transporter,
        private readonly string $defaultLabel,
    ) {
    }

    /**
     * Retrieve a text prompt by name. Uses default label if no version or label provided.
     *
     * @throws InvalidPromptTypeException
     */
    public function text(string $promptName, ?int $version = null, ?string $label = null, ?string $fallback = null): null|TextPromptResponse|FallbackPrompt
    {
        try {
            $prompt = $this->getPrompt($promptName, PromptType::TEXT, $version, $this->resolveLabel($version, $label));
        } catch (InvalidPromptTypeException $e) {
            throw $e;
        } catch (Throwable) {
            $prompt = null;
        }

        if ($prompt !== null) {
            /** @var array{id: string, name: string, prompt: string, type: string, config: array<string, mixed>, tags: array<int, string>, projectId: string, createdBy: string, createdAt: string, updatedAt: string, version: int, labels: array<int, string>, isActive: bool|null, commitMessage: string|null, resolutionGraph: array<int, mixed>|null} $prompt */
            return TextPromptResponse::fromArray($prompt);
        }

        return $fallback === null ? null : FallbackPrompt::text($fallback);
    }

    /**
     * Retrieve a chat prompt by name. Uses default label if no version or label provided.
     *
     * @param array<int, array{role: string, content: string}>|null $fallback
     *
     * @throws InvalidPromptTypeException
     */
    public function chat(string $promptName, ?int $version = null, ?string $label = null, ?array $fallback = null): null|ChatPromptResponse|FallbackPrompt
    {
        try {
            $prompt = $this->getPrompt($promptName, PromptType::CHAT, $version, $this->resolveLabel($version, $label));
        } catch (InvalidPromptTypeException $e) {
            throw $e;
        } catch (Throwable) {
            $prompt = null;
        }

        if ($prompt !== null) {
            /** @var array{id: string, name: string, prompt: array<int, array{role: string, content: string}>, type: string, config: array<string, mixed>, tags: array<int, string>, projectId: string, createdBy: string, createdAt: string, updatedAt: string, version: int, labels: array<int, string>, isActive: bool|null, commitMessage: string|null, resolutionGraph: array<int, mixed>|null} $prompt */
            return ChatPromptResponse::fromArray($prompt);
        }

        return $fallback === null ? null : FallbackPrompt::chat($fallback);
    }

    /**
     * List all prompts with optional filtering and automatic pagination.
     *
     * @return Generator<int, PromptListItem>
     *
     * @throws JsonException
     */
    public function list(?string $name = null, ?int $version = null, ?string $label = null, ?string $tag = null, ?string $fromUpdatedAt = null, ?string $toUpdatedAt = null): Generator
    {
        $page = 1;

        do {
            $response = $this->fetchPage($name, $version, $label, $tag, $page, $fromUpdatedAt, $toUpdatedAt);

            foreach ($response->data as $item) {
                yield $item;
            }

            $page++;
        } while ($page <= $response->meta->totalPages);
    }

    /**
     * Create a new prompt.
     *
     * @param ($type is PromptType::TEXT ? string : array<int, array{role: string, content: string}>) $prompt
     * @param array<int, string>|null $labels
     * @param array<string, mixed>|null $config
     * @param array<int, string>|null $tags
     * @return ($type is PromptType::TEXT ? TextPromptResponse : ChatPromptResponse)
     *
     * @throws JsonException
     */
    public function create(string $promptName, string|array $prompt, PromptType $type, ?array $labels = null, ?array $config = null, ?array $tags = null, ?string $commitMessage = null): TextPromptResponse|ChatPromptResponse
    {
        $response = $this->transporter->postJson('/api/public/v2/prompts', array_filter([
            'name' => $promptName,
            'prompt' => $prompt,
            'type' => $type->value,
            'config' => $config,
            'tags' => $tags,
            'labels' => $labels,
            'commitMessage' => $commitMessage,
        ]));

        $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        if ($type === PromptType::TEXT) {
            /** @var array{
             * id: string,
             * name: string,
             * prompt: string,
             * type: string,
             * config: array<string, mixed>,
             * tags: array<int, string>,
             * projectId: string,
             * createdBy: string,
             * createdAt: string,
             * updatedAt: string,
             * version: int,
             * labels: array<int,string>,
             * isActive: bool|null,
             * commitMessage: string|null,
             * resolutionGraph: null,
             * } $data
             */
            return TextPromptResponse::fromArray($data);
        }

        /** @var array{
         * id: string,
         * name: string,
         * prompt: array<int, array{role: string, content: string}>,
         * type: string,
         * config: array<string, mixed>,
         * tags: array<int, string>,
         * projectId: string,
         * createdBy: string,
         * createdAt: string,
         * updatedAt: string,
         * version: int,
         * labels: array<int,string>,
         * isActive: bool|null,
         * commitMessage: string|null,
         * resolutionGraph: null,
         * } $data
         */
        return ChatPromptResponse::fromArray($data);
    }

    /**
     * Update labels for a specific prompt version.
     *
     * @param array<int, string> $labels
     *
     * @throws JsonException
     */
    public function update(string $promptName, int $version, array $labels): TextPromptResponse|ChatPromptResponse
    {
        $response = $this->transporter->patchJson(
            uri: sprintf('/api/public/v2/prompts/%s/versions/%d', urlencode($promptName), $version),
            data: ['newLabels' => $labels],
        );

        /** @var array{type: string, id: string, name: string, prompt: string|array<int, array{role: string, content: string}>, config: array<string, mixed>, tags: array<int, string>, projectId: string, createdBy: string, createdAt: string, updatedAt: string, version: int, labels: array<int,string>, isActive: bool|null, commitMessage: string|null, resolutionGraph: array<int, mixed>|null} $data */
        $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        if ($data['type'] === PromptType::CHAT->value) {
            /** @var array{
             * id: string,
             * name: string,
             * prompt: array<int, array{role: string, content: string}>,
             * type: string,
             * config: array<string, mixed>,
             * tags: array<int, string>,
             * projectId: string,
             * createdBy: string,
             * createdAt: string,
             * updatedAt: string,
             * version: int,
             * labels: array<int,string>,
             * isActive: bool|null,
             * commitMessage: string|null,
             * resolutionGraph: array<int, mixed>|null,
             * } $data
             */
            return ChatPromptResponse::fromArray($data);
        }

        /** @var array{
         * id: string,
         * name: string,
         * prompt: string,
         * type: string,
         * config: array<string, mixed>,
         * tags: array<int, string>,
         * projectId: string,
         * createdBy: string,
         * createdAt: string,
         * updatedAt: string,
         * version: int,
         * labels: array<int,string>,
         * isActive: bool|null,
         * commitMessage: string|null,
         * resolutionGraph: array<int, mixed>|null,
         * } $data
         */
        return TextPromptResponse::fromArray($data);
    }

    /**
     * @throws JsonException
     */
    private function fetchPage(?string $name, ?int $version, ?string $label, ?string $tag, int $page, ?string $fromUpdatedAt, ?string $toUpdatedAt): PromptListResponse
    {
        $response = $this->transporter->get(
            uri: '/api/public/v2/prompts',
            options: ['query' => array_filter([
                'name' => $name,
                'version' => $version,
                'label' => $label,
                'tag' => $tag,
                'page' => $page,
                'fromUpdatedAt' => $fromUpdatedAt,
                'toUpdatedAt' => $toUpdatedAt,
            ])]
        );

        /** @var array{
         * data: array<int, array{name: string, tags: array<int, string>, lastUpdatedAt: string, versions: array<int, int>, labels: array<int, string>, lastConfig: array<string, mixed>}>,
         * meta: array{page: int, limit: int, totalPages: int, totalItems: int},
         * pagination: array{page: int, limit: int, totalPages: int, totalItems: int}
         * } $data
         */
        $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        return PromptListResponse::fromArray($data);
    }

    /**
     * Decide which label, if any, accompanies a prompt request.
     *
     * A label and a version are two ways of selecting the SAME thing — a prompt
     * revision — so the API refuses to receive both:
     *
     *     GET /api/public/v2/prompts/{name}?version=7&label=production
     *     -> 400 {"message":"Cannot specify both version and label",
     *             "error":"InvalidRequestError"}
     *
     * Applying the default label to a request that already names a version
     * therefore makes that request fail, which is what this method prevents.
     *
     * An explicitly passed $label is returned untouched: a caller who deliberately
     * sends both still gets that 400 back. This only stops the DEFAULT label from
     * being added behind their back.
     */
    private function resolveLabel(?int $version, ?string $label): ?string
    {
        if ($label !== null) {
            return $label;
        }

        return $version === null ? $this->defaultLabel : null;
    }

    /**
     * @return array{
     *     id: string,
     *     name: string,
     *     prompt: string|array<int, array{role: string, content: string}>,
     *     type: string,
     *     config: array<string, mixed>,
     *     tags: array<int, string>,
     *     projectId: string,
     *     createdBy: string,
     *     createdAt: string,
     *     updatedAt: string,
     *     version: int,
     *     labels: array<int, string>,
     *     isActive: bool|null,
     *     commitMessage: string|null,
     *     resolutionGraph: array<int, mixed>,
     * }
     *
     * @throws InvalidPromptTypeException
     * @throws JsonException
     * @throws Throwable
     */
    private function getPrompt(string $promptName, PromptType $type, ?int $version = null, ?string $label = null): array
    {
        $response = $this->transporter->get(
            uri: sprintf('/api/public/v2/prompts/%s', urlencode($promptName)),
            options: ['query' => array_filter([
                'version' => $version,
                'label' => $label,
            ])]
        );

        /** @var array{
         * id: string,
         * name: string,
         * prompt: ($type is "text" ? string : array<int, array{role: string, content: string}>),
         * type: string,
         * config: array<string, mixed>,
         * tags: array<int, string>,
         * projectId: string,
         * createdBy: string,
         * createdAt: string,
         * updatedAt: string,
         * version: int,
         * labels: array<int,string>,
         * isActive: bool|null,
         * commitMessage: string|null,
         * resolutionGraph: array<int, mixed>,
         * } $data
         */
        $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        if ($data['type'] !== $type->value) {
            throw InvalidPromptTypeException::fromMessage("{$promptName} returns a prompt of type {$data['type']}, but expected {$type->value}.");
        }

        return $data;
    }
}
