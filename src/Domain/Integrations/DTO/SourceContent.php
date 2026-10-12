<?php

namespace App\Domain\Integrations\DTO;

final readonly class SourceContent
{
    public string $plainText;

    public string $contentHash;

    /**
     * @param list<SourceContentChunk> $chunks
     */
    public function __construct(
        public array $chunks,
        public ?\DateTimeImmutable $externalUpdatedAt = null,
    ) {
        $this->plainText = implode("\n\n", array_map(
            static fn (SourceContentChunk $chunk): string => $chunk->text,
            $chunks,
        ));
        $this->contentHash = hash('sha256', $this->plainText);
    }

    public function isEmpty(): bool
    {
        return $this->chunks === [];
    }
}
