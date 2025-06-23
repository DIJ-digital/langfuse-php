<?php

declare(strict_types=1);

namespace DIJ\Langfuse\Contracts;

interface TransporterInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function get(string $uri, array $data = []): mixed;
}
