<?php

namespace App\Domain\Integrations\DTO;

final readonly class RemoteSource
{
    public function __construct(
        public string $externalId,
        public string $name,
        public ?string $url = null,
        public ?string $parentExternalId = null,
    ) {
    }
}
