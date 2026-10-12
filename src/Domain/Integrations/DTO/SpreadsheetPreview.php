<?php

namespace App\Domain\Integrations\DTO;

final readonly class SpreadsheetPreview
{
    /**
     * @param list<string> $sheetNames
     * @param array<string, string> $headers
     * @param list<array<string, string>> $rows
     */
    public function __construct(
        public array $sheetNames,
        public ?string $sheetName,
        public array $headers,
        public array $rows,
        public ?string $error = null,
    ) {
    }

    public static function unavailable(string $error): self
    {
        return new self([], null, [], [], $error);
    }

    public function isAvailable(): bool
    {
        return $this->error === null;
    }

    /**
     * @return array<string, string>
     */
    public function sheetChoices(): array
    {
        return array_combine($this->sheetNames, $this->sheetNames);
    }

    /**
     * @return array<string, string>
     */
    public function columnChoices(): array
    {
        $columnChoices = [];
        foreach ($this->headers as $columnLetter => $headerName) {
            $choiceLabel = $headerName === '' ? $columnLetter : sprintf('%s (%s)', $headerName, $columnLetter);
            $columnChoices[$choiceLabel] = $columnLetter;
        }

        return $columnChoices;
    }
}
