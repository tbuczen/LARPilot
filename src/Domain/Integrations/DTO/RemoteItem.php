<?php

namespace App\Domain\Integrations\DTO;

final readonly class RemoteItem
{
    public function __construct(
        public string $externalId,
        public string $title,
        public ?string $url = null,
        public ?\DateTimeImmutable $updatedAt = null,
    ) {
    }
}
