<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Miro\DTO;

final readonly class MiroGeometry
{
    public function __construct(
        public ?float $width,
        public ?float $height,
        public ?float $rotation,
    ) {
    }

    /**
     * @param array<mixed> $payload
     */
    public static function fromApiPayload(array $payload): self
    {
        return new self(
            width: MiroPayloadReader::float($payload, 'width'),
            height: MiroPayloadReader::float($payload, 'height'),
            rotation: MiroPayloadReader::float($payload, 'rotation'),
        );
    }
}
