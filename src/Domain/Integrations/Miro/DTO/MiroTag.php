<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Miro\DTO;

final readonly class MiroTag
{
    public function __construct(
        public string $id,
        public string $title,
        public ?string $fillColor,
    ) {
    }

    /**
     * @param array<mixed> $payload
     */
    public static function fromApiPayload(array $payload): self
    {
        return new self(
            id: MiroPayloadReader::string($payload, 'id') ?? '',
            title: MiroPayloadReader::string($payload, 'title') ?? '',
            fillColor: MiroPayloadReader::string($payload, 'fillColor'),
        );
    }
}
