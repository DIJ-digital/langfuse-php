<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\OtelIngestion;

use DIJ\Langfuse\PHP\Enums\ObservationLevel;

class Span extends Observation
{
    /**
     * @param array<string, mixed>|string|null $input
     * @param array<string, mixed>|string|null $output
     * @param array<string, mixed>|null $metadata
     */
    public function update(
        ?string $name = null,
        array|string|null $input = null,
        array|string|null $output = null,
        ?array $metadata = null,
        ?ObservationLevel $level = null,
        ?string $statusMessage = null,
    ): self {
        $this->set('langfuse.observation.type', 'span');
        $this->updateObservation($name, $input, $output, $metadata, $level, $statusMessage);

        return $this;
    }
}
