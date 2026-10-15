<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Miro\Import;

use App\Domain\Integrations\Miro\Snapshot\MiroBoardEntry;
use App\Domain\StoryObject\Entity\Enum\TargetType;

final class MiroTypeTags
{
    public const IMPORTABLE_TYPES = [
        TargetType::Character,
        TargetType::Faction,
        TargetType::Thread,
        TargetType::Quest,
        TargetType::Place,
        TargetType::Item,
        TargetType::Event,
    ];

    /**
     * @return list<TargetType>
     */
    public static function typesOf(MiroBoardEntry $entry): array
    {
        $types = [];
        foreach ($entry->tags as $tag) {
            $type = self::typeOf($tag);
            if ($type instanceof TargetType && !in_array($type, $types, true)) {
                $types[] = $type;
            }
        }

        return $types;
    }

    public static function typeOf(string $tag): ?TargetType
    {
        $normalizedTag = MiroTitleNormalizer::normalize($tag);
        foreach (self::IMPORTABLE_TYPES as $type) {
            if ($type->value === $normalizedTag) {
                return $type;
            }
        }

        return null;
    }

    public static function isImportable(TargetType $type): bool
    {
        return in_array($type, self::IMPORTABLE_TYPES, true);
    }
}
