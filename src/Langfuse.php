<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP;

use DIJ\Langfuse\PHP\Contracts\TransporterInterface;

class Langfuse
{
    public function __construct(
        private readonly TransporterInterface $transporter,
        private readonly string $environment = 'default',
        private readonly string $label = 'latest',
        private readonly string $version = '',
    ) {}

    public function prompt(): Prompt
    {
        return new Prompt(
            transporter: $this->transporter,
            defaultLabel: $this->label,
        );
    }

    public function ingestion(): Ingestion
    {
        return new Ingestion(
            transporter: $this->transporter,
            environment: $this->environment,
        );
    }

    public function otelIngestion(): OtelIngestion
    {
        return new OtelIngestion(
            transporter: $this->transporter,
            environment: $this->environment,
            version: $this->version,
        );
    }

    public function score(): Score
    {
        return new Score(
            transporter: $this->transporter,
        );
    }
}
