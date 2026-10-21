<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Integrations;

use App\Domain\Integrations\Entity\Enum\ResourceType;
use App\Domain\StoryObject\Entity\Enum\TargetType;
use PHPUnit\Framework\TestCase;

class ResourceTypeTest extends TestCase
{
    /**
     * @return iterable<string, array{ResourceType, TargetType}>
     */
    public static function expectedTargetTypeProvider(): iterable
    {
        yield 'character list' => [ResourceType::CHARACTER_LIST, TargetType::Character];
        yield 'character doc' => [ResourceType::CHARACTER_DOC, TargetType::Character];
        yield 'character doc directory' => [ResourceType::CHARACTER_DOC_DIRECTORY, TargetType::Character];
        yield 'character doc template' => [ResourceType::CHARACTER_DOC_TEMPLATE, TargetType::Character];
        yield 'event list' => [ResourceType::EVENT_LIST, TargetType::Event];
        yield 'event doc' => [ResourceType::EVENT_DOC, TargetType::Event];
        yield 'tag list' => [ResourceType::TAG_LIST, TargetType::Tag];
    }

    /**
     * @dataProvider expectedTargetTypeProvider
     */
    public function testMatchesOnlyItsTargetType(ResourceType $resourceType, TargetType $expectedTargetType): void
    {
        foreach (TargetType::cases() as $targetType) {
            $this->assertSame(
                $targetType === $expectedTargetType,
                $resourceType->matchesTargetType($targetType),
                sprintf('%s should match only %s, checked %s', $resourceType->value, $expectedTargetType->value, $targetType->value)
            );
        }
    }

    public function testCallTranscriptDirectoryMatchesNoTargetType(): void
    {
        foreach (TargetType::cases() as $targetType) {
            $this->assertFalse(ResourceType::CALL_TRANSCRIPT_DIRECTORY->matchesTargetType($targetType));
        }

        $this->assertTrue(ResourceType::CALL_TRANSCRIPT_DIRECTORY->isFolderMapping());
    }

    public function testEveryResourceTypeHasAnExpectedTargetType(): void
    {
        $coveredResourceTypes = array_map(
            static fn (array $case): ResourceType => $case[0],
            iterator_to_array(self::expectedTargetTypeProvider())
        );

        $this->assertEqualsCanonicalizing(ResourceType::cases(), [...array_values($coveredResourceTypes), ResourceType::CALL_TRANSCRIPT_DIRECTORY]);
    }
}
