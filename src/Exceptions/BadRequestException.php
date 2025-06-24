<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Exceptions;

use Exception;

class BadRequestException extends Exception
{
    public static function fromMessage(string $message): self
    {
        return new self($message);
    }
}
