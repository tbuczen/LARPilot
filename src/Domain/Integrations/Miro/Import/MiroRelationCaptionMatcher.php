<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Miro\Import;

use App\Domain\StoryObject\Entity\Enum\RelationType;

final class MiroRelationCaptionMatcher
{
    public const SYNONYMS = [
        'friend' => [
            'friend', 'friends', 'friendship', 'best friend', 'best friends', 'buddy', 'buddies', 'pal',
            'przyjaciel', 'przyjaciele', 'przyjaciolka', 'przyjazn', 'kolega', 'kolezanka', 'kumpel',
        ],
        'enemy' => [
            'enemy', 'enemies', 'foe', 'foes', 'rival', 'rivals', 'rivalry', 'nemesis', 'hates', 'hate', 'hatred', 'feud',
            'wrog', 'wrogowie', 'wrogosc', 'rywal', 'rywale', 'rywalka', 'nienawisc', 'nienawidzi',
        ],
        'family' => [
            'family', 'relative', 'relatives', 'kin', 'sibling', 'siblings', 'brother', 'sister', 'parent', 'mother',
            'father', 'son', 'daughter', 'child', 'cousin', 'uncle', 'aunt', 'nephew', 'niece', 'grandparent',
            'grandmother', 'grandfather', 'spouse', 'husband', 'wife',
            'rodzina', 'krewny', 'krewna', 'rodzenstwo', 'brat', 'siostra', 'rodzic', 'matka', 'ojciec', 'syn',
            'corka', 'dziecko', 'kuzyn', 'kuzynka', 'wujek', 'ciocia', 'babcia', 'dziadek', 'maz', 'zona',
        ],
        'ally' => [
            'ally', 'allies', 'allied', 'alliance', 'partner', 'partners', 'supports', 'protects',
            'sojusznik', 'sojusznicy', 'sojuszniczka', 'sojusz', 'wspiera', 'chroni',
        ],
    ];

    public static function match(string $caption): ?RelationType
    {
        $normalizedCaption = MiroTitleNormalizer::normalize($caption);
        if ($normalizedCaption === '') {
            return null;
        }

        foreach (self::SYNONYMS as $relationTypeValue => $synonyms) {
            if (in_array($normalizedCaption, $synonyms, true)) {
                return RelationType::from($relationTypeValue);
            }
        }

        return null;
    }
}
