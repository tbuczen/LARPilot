<?php

namespace App\Domain\CallTranscript\DTO;

final readonly class TranscriptSegment
{
    public function __construct(
        public float $startSeconds,
        public string $text,
    ) {
    }

    /**
     * @return array{start: float, text: string}
     */
    public function toArray(): array
    {
        return ['start' => $this->startSeconds, 'text' => $this->text];
    }

    /**
     * @param array{start?: mixed, text?: mixed} $data
     */
    public static function fromArray(array $data): self
    {
        return new self((float) ($data['start'] ?? 0), (string) ($data['text'] ?? ''));
    }
}
