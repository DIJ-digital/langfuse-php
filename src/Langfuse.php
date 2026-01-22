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

    public function score(): Score
    {
        return new Score(
            transporter: $this->transporter,
        );
    }

    public function scoreConfig(): ScoreConfig
    {
        return new ScoreConfig(
            transporter: $this->transporter,
        );
    }
}
