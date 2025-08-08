<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP;

use DIJ\Langfuse\PHP\Contracts\TransporterInterface;
use DIJ\Langfuse\PHP\Exceptions\LangfuseException;

class Ingestion
{
    public function __construct(
        private readonly TransporterInterface $transporter,
        private readonly string $environment = 'default',
    ) {
    }

    /**
     * @param array<string, mixed>|string|null $input
     * @param array<string, mixed>|string|null $output
     * @param array<string, mixed>|null $metadata
     */
    public function trace(
        array|string|null $input,
        array|string|null $output,
        string $traceId,
        string $name,
        ?string $sessionId = null,
        ?array $metadata = null,
    ): void {
        $envelope = [
            'id' => $this->uuid(),
            'timestamp' => gmdate('c'),
            'type' => 'trace-create',
            'body' => array_filter([
                'id' => $traceId,
                'environment' => $this->environment,
                'timestamp' => gmdate('c'),
                'sessionId' => $sessionId,
                'name' => $name,
                'input' => $input,
                'output' => $output,
                'metadata' => $metadata,
            ], static fn (mixed $v): bool => $v !== null),
        ];

        $this->postBatch([$envelope]);
    }

    /**
     * @param array<string, mixed>|string $input
     * @param array<string, mixed>|string $output
     * @param array<string, mixed>|null $modelParameters
     * @param array<string, mixed>|null $metadata
     */
    public function generation(
        array|string $input,
        array|string $output,
        string $traceId,
        string $name,
        ?string $sessionId = null,
        ?string $promptName = null,
        ?int $promptVersion = null,
        ?string $model = null,
        ?array $modelParameters = null,
        ?array $metadata = null,
    ): void {
        $envelope = [
            'id' => $this->uuid(),
            'timestamp' => gmdate('c'),
            'type' => 'generation-create',
            'body' => array_filter([
                'id' => $this->uuid(),
                'traceId' => $traceId,
                'environment' => $this->environment,
                'timestamp' => gmdate('c'),
                'sessionId' => $sessionId,
                'name' => $name,
                'input' => $input,
                'output' => $output,
                'model' => $model,
                'modelParameters' => $modelParameters,
                'promptName' => $promptName,
                'promptVersion' => $promptVersion,
                'metadata' => $metadata,
            ], static fn (mixed $v): bool => $v !== null),
        ];

        $this->postBatch([$envelope]);
    }

    /**
     * @param array<int, array<string, mixed>> $batch
     */
    private function postBatch(array $batch): void
    {
        $response = $this->transporter->postJson('/api/public/ingestion', ['batch' => $batch]);
        $status = $response->getStatusCode();

        if ($status !== 207 && $status !== 200) {
            throw LangfuseException::fromMessage("Unexpected status code {$status} from ingestion");
        }
    }

    private function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0F) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3F) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
