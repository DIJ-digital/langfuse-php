<?php

declare(strict_types=1);

namespace DIJ\Langfuse\Enums;

enum PromptType: string
{
    case CHAT = 'chat';
    case TEXT = 'text';
}
