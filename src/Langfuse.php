<?php

declare(strict_types=1);

namespace DIJ\Langfuse;

use DIJ\Langfuse\Contracts\TransporterInterface;

class Langfuse
{
    public function __construct(private TransporterInterface $transporter) {}

    public function prompt(): Prompt
    {
        return new Prompt($this->transporter);
    }
}
