<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Testing\Responses;

use GuzzleHttp\Psr7\Response;

class PatchMediaResponse extends Response
{
    /**
     * @param  array<array<string>|string>  $headers
     */
    public function __construct(int $status = 204, array $headers = [], string $version = '1.1', ?string $reason = null)
    {
        parent::__construct($status, $headers, '', $version, $reason);
    }
}
