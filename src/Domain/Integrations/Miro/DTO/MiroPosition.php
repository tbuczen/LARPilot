<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Miro\DTO;

final readonly class MiroPosition
{
    public function __construct(
        public float $x,
        public float $y,
        public ?string $relativeTo,
    ) {
    }

    /**
     * @param array<mixed> $payload
     */
    public static function fromApiPayload(array $payload): self
    {
        return new self(
            x: MiroPayloadReader::float($payload, 'x') ?? 0.0,
            y: MiroPayloadReader::float($payload, 'y') ?? 0.0,
            relativeTo: MiroPayloadReader::string($payload, 'relativeTo'),
        );
    }
}
