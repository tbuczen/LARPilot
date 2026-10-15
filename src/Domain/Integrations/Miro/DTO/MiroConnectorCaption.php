<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Miro\DTO;

final readonly class MiroConnectorCaption
{
    public function __construct(
        public string $content,
        public ?string $position,
    ) {
    }

    /**
     * @param array<mixed> $payload
     */
    public static function fromApiPayload(array $payload): self
    {
        return new self(
            content: MiroPayloadReader::string($payload, 'content') ?? '',
            position: MiroPayloadReader::string($payload, 'position'),
        );
    }
}
