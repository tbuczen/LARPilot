<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Miro\DTO;

use DateTimeImmutable;

final readonly class MiroBoard
{
    public const UNVERIFIED_FIELDS = ['team.id', 'modifiedAt'];

    public function __construct(
        public string $id,
        public string $name,
        public ?string $description,
        public ?string $viewLink,
        public ?string $teamId,
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
            name: MiroPayloadReader::string($payload, 'name') ?? '',
            description: MiroPayloadReader::string($payload, 'description'),
            viewLink: MiroPayloadReader::string($payload, 'viewLink'),
            teamId: MiroPayloadReader::nestedId($payload, 'team'),
            createdAt: MiroPayloadReader::dateTime($payload, 'createdAt'),
            modifiedAt: MiroPayloadReader::dateTime($payload, 'modifiedAt'),
        );
    }
}
