<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\OtelIngestion;

use DIJ\Langfuse\PHP\Enums\ObservationLevel;

class Generation extends Observation
{
    /**
     * @param array<string, mixed>|string|null $input
     * @param array<string, mixed>|string|null $output
     * @param array<string, mixed>|null $modelParameters
     * @param array<string, int>|null $usageDetails
     * @param array<string, mixed>|null $metadata
     */
    public function update(
        ?string $name = null,
        array|string|null $input = null,
        array|string|null $output = null,
        ?string $model = null,
        ?array $modelParameters = null,
        ?array $usageDetails = null,
        ?string $promptName = null,
        ?int $promptVersion = null,
        ?array $metadata = null,
        ?ObservationLevel $level = null,
        ?string $statusMessage = null,
    ): self {
        $this->set('langfuse.observation.type', 'generation');
        $this->set('langfuse.observation.model.name', $model);
        $this->set('langfuse.observation.model.parameters', $modelParameters);
        $this->set('langfuse.observation.usage_details', $usageDetails);
        $this->set('langfuse.observation.prompt.name', $promptName);
        $this->set('langfuse.observation.prompt.version', $promptVersion);
        $this->updateObservation($name, $input, $output, $metadata, $level, $statusMessage);

        return $this;
    }
}
