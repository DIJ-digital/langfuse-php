<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\OtelIngestion;

use DIJ\Langfuse\PHP\Enums\ObservationLevel;

class Trace extends Observation
{
    /**
     * @param array<string, mixed>|string|null $input
     * @param array<string, mixed>|string|null $output
     * @param array<string, mixed>|null $metadata
     * @param list<string>|null $tags
     */
    public function update(
        ?string $name = null,
        ?string $sessionId = null,
        ?string $userId = null,
        array|string|null $input = null,
        array|string|null $output = null,
        ?array $metadata = null,
        ?array $tags = null,
        ?bool $public = null,
        ?string $release = null,
        ?ObservationLevel $level = null,
        ?string $statusMessage = null,
    ): self {
        $this->set('langfuse.observation.type', 'span');
        $this->set('langfuse.trace.input', $input);
        $this->set('langfuse.trace.output', $output);
        $this->updateObservation($name, $input, $output, null, $level, $statusMessage);

        $this->setTraceAttribute('langfuse.trace.name', $name);
        $this->setTraceAttribute('langfuse.session.id', $sessionId);
        $this->setTraceAttribute('langfuse.user.id', $userId);
        $this->setTraceAttribute('langfuse.trace.public', $public);
        $this->setTraceAttribute('langfuse.release', $release);

        if ($tags !== null) {
            $this->traceAttributes['langfuse.trace.tags'] = $tags;
        }

        foreach ($metadata ?? [] as $key => $value) {
            $this->setTraceAttribute('langfuse.trace.metadata.' . $key, $value);
        }

        return $this;
    }
}
