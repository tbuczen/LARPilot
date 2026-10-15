<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Miro\DTO;

use DateTimeImmutable;

final readonly class MiroConnector
{
    public const UNVERIFIED_FIELDS = ['captions.position', 'modifiedAt'];

    /**
     * @param list<MiroConnectorCaption> $captions
     */
    public function __construct(
        public string $id,
        public ?string $startItemId,
        public ?string $endItemId,
        public array $captions,
        public ?string $shape,
        public ?DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $modifiedAt,
    ) {
    }

    /**
     * @param array<mixed> $payload
     */
    public static function fromApiPayload(array $payload): self
    {
        return new self(
            id: MiroPayloadReader::string($payload, 'id') ?? '',
            startItemId: MiroPayloadReader::nestedId($payload, 'startItem'),
            endItemId: MiroPayloadReader::nestedId($payload, 'endItem'),
            captions: array_map(
                MiroConnectorCaption::fromApiPayload(...),
                MiroPayloadReader::listOfMaps($payload, 'captions'),
            ),
            shape: MiroPayloadReader::string($payload, 'shape'),
            createdAt: MiroPayloadReader::dateTime($payload, 'createdAt'),
            modifiedAt: MiroPayloadReader::dateTime($payload, 'modifiedAt'),
        );
    }

    public function getCaptionText(): string
    {
        $captionContents = array_map(
            static fn (MiroConnectorCaption $caption): string => strip_tags($caption->content),
            $this->captions,
        );

        return trim(implode(' ', $captionContents));
    }
}
