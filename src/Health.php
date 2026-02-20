<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP;

use DIJ\Langfuse\PHP\Contracts\TransporterInterface;
use DIJ\Langfuse\PHP\Responses\HealthResponse;
use JsonException;

class Health
{
    public function __construct(private readonly TransporterInterface $transporter) {}

    /**
     * @throws JsonException
     */
    public function check(): HealthResponse
    {
        $response = $this->transporter->get(
            uri: '/api/public/health',
        );

        /** @var array{status: string} $data */
        $data = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        return HealthResponse::fromArray($data);
    }
}
