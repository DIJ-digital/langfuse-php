<?php

declare(strict_types=1);

namespace DIJ\Langfuse\Exceptions;

use Exception;

class MethodNotAllowedException extends Exception
{
    public static function fromMessage(string $message): self
    {
        return new self($message);
    }
}
