<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP;

use DIJ\Langfuse\PHP\Contracts\TransporterInterface;

class Langfuse
{
    public function __construct(private readonly TransporterInterface $transporter) {}

    public function prompt(): Prompt
    {
        return new Prompt($this->transporter);
    }

    public function ingestion(): Ingestion
    {
        return new Ingestion($this->transporter);
    }
}
