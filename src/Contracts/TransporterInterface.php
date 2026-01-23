<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Contracts;

use Psr\Http\Message\ResponseInterface;

interface TransporterInterface
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function request(string $method, string $uri, array $options = []): ResponseInterface;

    /**
     * @param  array<string, mixed>  $options
     */
    public function get(string $uri, array $options = []): ResponseInterface;

    /**
     * @param  array<string, mixed>  $options
     */
    public function post(string $uri, array $options = []): ResponseInterface;

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $options
     */
    public function postJson(string $uri, array $data = [], array $options = []): ResponseInterface;
}
