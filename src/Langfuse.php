<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP;

use DIJ\Langfuse\PHP\Contracts\TransporterInterface;

class Langfuse
{
    public function __construct(private readonly TransporterInterface $transporter) {}

    public function prompt(): Prompt
    {
        return new Prompt(
            transporter: $this->transporter,
        );
    }

    public function ingestion(string $environment = 'default'): Ingestion
    {
        return new Ingestion(
            transporter: $this->transporter,
            environment: $environment,
        );
    }

    public function score(): Score
    {
        return new Score(
            transporter: $this->transporter,
        );
    }
}
