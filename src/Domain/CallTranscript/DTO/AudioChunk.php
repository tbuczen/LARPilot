<?php

namespace App\Domain\CallTranscript\DTO;

final readonly class AudioChunk
{
    public function __construct(
        public string $path,
        public float $offsetSeconds,
        public bool $temporary,
    ) {
    }
}
