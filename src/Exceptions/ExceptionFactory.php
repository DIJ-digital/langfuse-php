<?php

declare(strict_types=1);

namespace DIJ\Langfuse\Exceptions;

class ExceptionFactory
{
    public static function createFromStatusCode(int $statusCode, string $message = ''): BadRequestException|UnauthorizedException|ForbiddenException|NotFoundException|MethodNotAllowedException|InternalServerErrorException|LangfuseException
    {
        return match ($statusCode) {
            400 => BadRequestException::fromMessage($message ?: 'Bad Request'),
            401 => UnauthorizedException::fromMessage($message ?: 'Unauthorized'),
            403 => ForbiddenException::fromMessage($message ?: 'Forbidden'),
            404 => NotFoundException::fromMessage($message ?: 'Not Found'),
            405 => MethodNotAllowedException::fromMessage($message ?: 'Method Not Allowed'),
            500 => InternalServerErrorException::fromMessage($message ?: 'Internal Server Error'),
            default => LangfuseException::fromMessage($message ?: "HTTP Error: {$statusCode}"),
        };
    }
}
