<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Exceptions;

class BadRequestException extends LangfuseException
{
    public static function fromMessage(string $message): self
    {
        return new self($message);
    }
}
