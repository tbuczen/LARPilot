<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\StoryObject;

use App\Domain\StoryObject\Entity\Enum\RelationType;
use App\Domain\StoryObject\Entity\Enum\TargetType;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for RelationType::getCompatibleTargetTypesFor()
 */
class RelationTypeCompatibilityTest extends TestCase
{
    /**
     * @dataProvider interpersonalRelationKindProvider
     */
    public function testCharacterOrFactionSourceAllowsOnlyCharacterAndFaction(RelationType $relationType): void
    {
        $this->assertSame(
            [TargetType::Character, TargetType::Faction],
            $relationType->getCompatibleTargetTypesFor(TargetType::Character)
        );
        $this->assertSame(
            [TargetType::Character, TargetType::Faction],
            $relationType->getCompatibleTargetTypesFor(TargetType::Faction)
        );
    }

    /**
     * @dataProvider interpersonalRelationKindProvider
     */
    public function testNonInterpersonalSourceHasNoCompatibleTargets(RelationType $relationType): void
    {
        foreach ([TargetType::Thread, TargetType::Event, TargetType::Item, TargetType::Place] as $fromType) {
            $this->assertSame([], $relationType->getCompatibleTargetTypesFor($fromType));
        }
    }

    public static function interpersonalRelationKindProvider(): array
    {
        return [
            'friend' => [RelationType::Friend],
            'enemy' => [RelationType::Enemy],
            'family' => [RelationType::Family],
            'ally' => [RelationType::Ally],
        ];
    }
}
