<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Transporters;

use DIJ\Langfuse\PHP\Contracts\TransporterInterface;
use DIJ\Langfuse\PHP\Exceptions\BadRequestException;
use DIJ\Langfuse\PHP\Exceptions\ExceptionFactory;
use DIJ\Langfuse\PHP\Exceptions\ForbiddenException;
use DIJ\Langfuse\PHP\Exceptions\InternalServerErrorException;
use DIJ\Langfuse\PHP\Exceptions\LangfuseException;
use DIJ\Langfuse\PHP\Exceptions\MethodNotAllowedException;
use DIJ\Langfuse\PHP\Exceptions\NotFoundException;
use DIJ\Langfuse\PHP\Exceptions\UnauthorizedException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

class HttpTransporter implements TransporterInterface
{
    public function __construct(
        public readonly ClientInterface $client
    ) {}

    /**
     * @throws BadRequestException
     * @throws UnauthorizedException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws MethodNotAllowedException
     * @throws InternalServerErrorException
     * @throws LangfuseException
     */
    public function request(string $method, string $uri, array $options = []): ResponseInterface
    {
        try {
            $response = $this->client->request($method, $uri, $options);
        } catch (GuzzleException $e) {
            throw ExceptionFactory::createFromStatusCode($e->getCode(), $e->getMessage());
        }

        return $response;
    }

    /**
     * @param  array<string, mixed>  $options
     *
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws InternalServerErrorException
     * @throws LangfuseException
     * @throws MethodNotAllowedException
     * @throws NotFoundException
     * @throws UnauthorizedException
     */
    public function get(string $uri, array $options = []): ResponseInterface
    {
        return $this->request('GET', $uri, $options);
    }

    /**
     * @param  array<string, mixed>  $options
     *
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws InternalServerErrorException
     * @throws LangfuseException
     * @throws MethodNotAllowedException
     * @throws NotFoundException
     * @throws UnauthorizedException
     */
    public function post(string $uri, array $options = []): ResponseInterface
    {
        return $this->request('POST', $uri, $options);
    }

    public function postJson(string $uri, array $data = [], array $options = []): ResponseInterface
    {
        return $this->request('POST', $uri, array_merge(['body' => json_encode($data)], array_merge(['headers' => ['Content-Type' => 'application/json']], $options)));
    }
}
