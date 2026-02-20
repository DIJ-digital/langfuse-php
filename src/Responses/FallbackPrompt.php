<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Responses;

use DIJ\Langfuse\PHP\Concerns\IsCompilable;
use DIJ\Langfuse\PHP\Enums\PromptType;

readonly class FallbackPrompt extends BasePromptResponse
{
    use IsCompilable;

    /**
     * Create a text fallback prompt
     */
    public static function text(
        string $content,
    ): self {
        return new self(
            prompt: $content,
            type: PromptType::TEXT->value,
        );
    }

    /**
     * Create a chat fallback prompt
     *
     * @param array<int, array{role: string, content: string}> $content
     */
    public static function chat(
        array $content,
    ): self {
        return new self(
            prompt: $content,
            type: PromptType::CHAT->value,
        );
    }
}
