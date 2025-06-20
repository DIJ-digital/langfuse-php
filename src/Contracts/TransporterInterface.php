<?php

declare(strict_types=1);

namespace DIJ\Langfuse\Contracts;

interface TransporterInterface
{
    public function get(string $uri, array $data = []): array;
}
