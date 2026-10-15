<?php

namespace App\Domain\StoryObject\Entity\Enum;

enum RelationType: string
{
    case Friend = 'friend';
    case Enemy = 'enemy';
    case Family = 'family';
    case Ally = 'ally';

    /**
     * Which TargetType(s) can be the other side of this relation kind, given
     * a source of type $from. All current kinds are interpersonal, so only
     * Character/Faction can be paired with each other; a source of any other
     * type has no valid target for these kinds. A future non-interpersonal
     * kind (e.g. "owns") would add its own arm here.
     *
     * @return TargetType[]
     */
    public function getCompatibleTargetTypesFor(TargetType $from): array
    {
        return match ($this) {
            self::Friend, self::Enemy, self::Family, self::Ally => match ($from) {
                TargetType::Character, TargetType::Faction => [TargetType::Character, TargetType::Faction],
                default => [],
            },
        };
    }
}
