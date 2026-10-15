<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Integrations\Miro\Import;

use App\Domain\Integrations\Miro\Import\MiroRelationCaptionMatcher;
use App\Domain\StoryObject\Entity\Enum\RelationType;
use Codeception\Test\Unit;

class MiroRelationCaptionMatcherTest extends Unit
{
    /**
     * @return iterable<string, array{string, RelationType}>
     */
    public static function knownCaptions(): iterable
    {
        yield 'type name' => ['friend', RelationType::Friend];
        yield 'capitalised plural' => ['Enemies', RelationType::Enemy];
        yield 'family member' => ['Cousin', RelationType::Family];
        yield 'ally with punctuation' => ['ally!', RelationType::Ally];
        yield 'polish with accents' => ['Przyjaźń', RelationType::Friend];
        yield 'polish enemy' => ['wróg', RelationType::Enemy];
        yield 'polish family' => ['Córka', RelationType::Family];
    }

    /**
     * @dataProvider knownCaptions
     */
    public function testMatchesSynonyms(string $caption, RelationType $expected): void
    {
        $this->assertSame($expected, MiroRelationCaptionMatcher::match($caption));
    }

    public function testNeverGuessesUnknownOrEmptyCaptions(): void
    {
        $this->assertNull(MiroRelationCaptionMatcher::match('suspects'));
        $this->assertNull(MiroRelationCaptionMatcher::match('owes a favour secret'));
        $this->assertNull(MiroRelationCaptionMatcher::match('not a friend'));
        $this->assertNull(MiroRelationCaptionMatcher::match(''));
    }

    public function testEverySynonymIsStoredNormalizedAndMapsToARelationType(): void
    {
        foreach (MiroRelationCaptionMatcher::SYNONYMS as $relationTypeValue => $synonyms) {
            $this->assertInstanceOf(RelationType::class, RelationType::tryFrom($relationTypeValue));
            foreach ($synonyms as $synonym) {
                $this->assertSame(RelationType::from($relationTypeValue), MiroRelationCaptionMatcher::match($synonym), $synonym);
            }
        }
    }
}
