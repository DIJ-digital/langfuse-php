<?php
declare(strict_types=1);


namespace DIJ\Langfuse\Concerns;

trait IsCompilable
{
    /**
     * @param ...$data
     * @return string|array{role: string, content: string}
     */
    public function compile(array $param = []): string|array
    {
        return is_array($this->prompt)
            ? $this->compileArray($this->prompt, ...$param)
            : $this->compileString($this->prompt, ...$param);
    }

    private function compileArray(array $prompt, ...$data): array
    {
        foreach ($prompt as $key => $promptValue) {
            $prompt[$key]['content'] = $this->compileString($promptValue['content'], ...$data);
        }

        return $prompt;
    }

    private function  compileString(string $prompt, ...$data): string
    {
        return str_replace(
            array_map(fn($i) => '{{' . $i . '}}', array_keys($data)),
            array_values($data),
            $prompt
        );
    }

}
