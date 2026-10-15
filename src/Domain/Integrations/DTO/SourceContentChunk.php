<?php

namespace App\Domain\Integrations\DTO;

final readonly class SourceContentChunk
{
    public function __construct(
        public int $position,
        public string $text,
        public ?string $author = null,
        public ?\DateTimeImmutable $postedAt = null,
        public ?string $permalink = null,
    ) {
    }
}
