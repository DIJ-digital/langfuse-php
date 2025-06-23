<?php

declare(strict_types=1);

namespace DIJ\Langfuse\Exceptions;

class ExceptionFactory
{
    public static function createFromStatusCode(int $statusCode, string $message = ''): BadRequestException|UnauthorizedException|ForbiddenException|NotFoundException|MethodNotAllowedException|InternalServerErrorException|LangfuseException
    {
        return match ($statusCode) {
            400 => BadRequestException::fromMessage($message !== '' && $message !== '0' ? $message : 'Bad Request'),
            401 => UnauthorizedException::fromMessage($message !== '' && $message !== '0' ? $message : 'Unauthorized'),
            403 => ForbiddenException::fromMessage($message !== '' && $message !== '0' ? $message : 'Forbidden'),
            404 => NotFoundException::fromMessage($message !== '' && $message !== '0' ? $message : 'Not Found'),
            405 => MethodNotAllowedException::fromMessage($message !== '' && $message !== '0' ? $message : 'Method Not Allowed'),
            500 => InternalServerErrorException::fromMessage($message !== '' && $message !== '0' ? $message : 'Internal Server Error'),
            default => LangfuseException::fromMessage($message !== '' && $message !== '0' ? $message : "HTTP Error: {$statusCode}"),
        };
    }
}
