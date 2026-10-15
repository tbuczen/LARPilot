<?php

namespace App\Domain\Integrations\DTO;

final readonly class SourceContent
{
    public string $plainText;

    public string $contentHash;

    /**
     * @param list<SourceContentChunk> $chunks
     * @param list<string> $tags
     */
    public function __construct(
        public array $chunks,
        public ?\DateTimeImmutable $externalUpdatedAt = null,
        public ?string $title = null,
        public array $tags = [],
    ) {
        $this->plainText = implode("\n\n", array_map(
            static fn (SourceContentChunk $chunk): string => $chunk->text,
            $chunks,
        ));
        $this->contentHash = hash('sha256', implode("\n\n", array_filter(
            [$title ?? '', implode(', ', $tags), $this->plainText],
            static fn (string $part): bool => $part !== '',
        )));
    }

    public function isEmpty(): bool
    {
        return $this->chunks === [];
    }
}
