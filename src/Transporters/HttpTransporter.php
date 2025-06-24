<?php

declare(strict_types=1);

namespace DIJ\Langfuse\Transporters;

use DIJ\Langfuse\Contracts\TransporterInterface;
use DIJ\Langfuse\Exceptions\BadRequestException;
use DIJ\Langfuse\Exceptions\ExceptionFactory;
use DIJ\Langfuse\Exceptions\ForbiddenException;
use DIJ\Langfuse\Exceptions\InternalServerErrorException;
use DIJ\Langfuse\Exceptions\LangfuseException;
use DIJ\Langfuse\Exceptions\MethodNotAllowedException;
use DIJ\Langfuse\Exceptions\NotFoundException;
use DIJ\Langfuse\Exceptions\UnauthorizedException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

class HttpTransporter implements TransporterInterface
{
    public function __construct(
        public readonly ClientInterface $client
    ) {
    }

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
     * @param array<string, mixed> $options
     *
     * @throws GuzzleException
     */
    public function get(string $uri, array $options = []): ResponseInterface
    {
        return $this->request('GET', $uri, $options);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @throws GuzzleException
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
