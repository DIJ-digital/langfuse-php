<?php

declare(strict_types=1);

namespace DIJ\Langfuse\Concerns;

trait IsCompilable
{
    /**
     * @param array<string, string> $param
     * @return array<int, array{role: string, content: string}>|string
     */
    public function compile(array $param = []): array|string
    {
        return is_array($this->prompt) ? $this->compileArray($this->prompt, $param) : $this->compileString($this->prompt, $param);
    }

    /**
     * @param array<string, string> $data
     * @param array<int, array{role: string, content: string}> $prompt
     * @return array<int, array{role: string, content: string}>
     */
    private function compileArray(array $prompt, array $data = []): array
    {
        foreach ($prompt as $key => $promptValue) {
            $prompt[$key]['content'] = $this->compileString($promptValue['content'], $data);
        }

        return $prompt;
    }

    /**
     * @param array<string, string> $data
     */
    private function compileString(string $prompt, array $data = []): string
    {
        /** @var array<int, string> $values */
        $values = array_values($data);

        return str_replace(
            array_map(fn ($i): string => '{{' . $i . '}}', array_keys($data)),
            $values,
            $prompt
        );
    }
}
