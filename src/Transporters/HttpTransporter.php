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

class HttpTransporter implements TransporterInterface
{
    /**
     * @param  array<string, mixed>  $headers
     * @param  array<string, mixed>  $clientOptions
     */
    public function __construct(
        public readonly ClientInterface $client
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, mixed>
     *
     * @throws BadRequestException
     * @throws UnauthorizedException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws MethodNotAllowedException
     * @throws InternalServerErrorException
     * @throws LangfuseException
     */
    public function get(string $uri, array $data = []): array
    {
        try {

            $response = $this->client->request('GET', $uri, $data);

            $responseData = json_decode($response->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Failed to decode JSON response: '.json_last_error_msg());
            }

            return $responseData;
        } catch (GuzzleException $e) {
            throw ExceptionFactory::createFromStatusCode($e->getCode(), $e->getMessage());
        }
    }
}
