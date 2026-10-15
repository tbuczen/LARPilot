<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Miro\DTO;

use DateTimeImmutable;

final readonly class MiroItem
{
    public const UNVERIFIED_FIELDS = ['tagIds', 'data.tagIds', 'data.description', 'parent.id', 'modifiedAt'];

    /**
     * @param list<string> $tagIds
     */
    public function __construct(
        public string $id,
        public string $type,
        public ?string $title,
        public ?string $htmlContent,
        public ?string $plainContent,
        public ?MiroPosition $position,
        public ?MiroGeometry $geometry,
        public ?string $parentId,
        public ?DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $modifiedAt,
        public array $tagIds,
    ) {
    }

    /**
     * @param array<mixed> $payload
     */
    public static function fromApiPayload(array $payload): self
    {
        $data = MiroPayloadReader::map($payload, 'data');
        $htmlContent = MiroPayloadReader::string($data, 'content') ?? MiroPayloadReader::string($data, 'description');
        $position = MiroPayloadReader::map($payload, 'position');
        $geometry = MiroPayloadReader::map($payload, 'geometry');

        return new self(
            id: MiroPayloadReader::string($payload, 'id') ?? '',
            type: MiroPayloadReader::string($payload, 'type') ?? '',
            title: MiroPayloadReader::string($data, 'title'),
            htmlContent: $htmlContent,
            plainContent: self::toPlainText($htmlContent),
            position: $position === [] ? null : MiroPosition::fromApiPayload($position),
            geometry: $geometry === [] ? null : MiroGeometry::fromApiPayload($geometry),
            parentId: MiroPayloadReader::nestedId($payload, 'parent'),
            createdAt: MiroPayloadReader::dateTime($payload, 'createdAt'),
            modifiedAt: MiroPayloadReader::dateTime($payload, 'modifiedAt'),
            tagIds: array_values(array_unique([
                ...MiroPayloadReader::listOfStrings($payload, 'tagIds'),
                ...MiroPayloadReader::listOfStrings($data, 'tagIds'),
            ])),
        );
    }

    private static function toPlainText(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $withLineBreaks = preg_replace('#<br\s*/?>|</p>\s*<p[^>]*>|</li>\s*<li[^>]*>#i', "\n", $html) ?? $html;
        $text = html_entity_decode(strip_tags($withLineBreaks), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim($text);
    }
}
