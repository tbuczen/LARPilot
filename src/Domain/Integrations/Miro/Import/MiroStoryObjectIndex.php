<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Miro\Import;

use App\Domain\StoryObject\Entity\Enum\TargetType;
use App\Domain\StoryObject\Entity\StoryObject;

final class MiroStoryObjectIndex
{
    /**
     * @var array<string, array<string, list<StoryObject>>>
     */
    private array $byTypeAndNormalizedTitle = [];

    /**
     * @var array<string, list<StoryObject>>
     */
    private array $byExactTitle = [];

    /**
     * @param iterable<StoryObject> $storyObjects
     */
    public function __construct(iterable $storyObjects)
    {
        foreach ($storyObjects as $storyObject) {
            $this->add($storyObject);
        }
    }

    public function add(StoryObject $storyObject): void
    {
        $type = $storyObject::getTargetType();
        if (!MiroTypeTags::isImportable($type)) {
            return;
        }

        $this->byTypeAndNormalizedTitle[$type->value][MiroTitleNormalizer::normalize($storyObject->getTitle())][] = $storyObject;
        $this->byExactTitle[MiroTitleNormalizer::exact($storyObject->getTitle())][] = $storyObject;
    }

    /**
     * @return list<StoryObject>
     */
    public function matchingType(TargetType $type, string $title): array
    {
        $normalizedTitle = MiroTitleNormalizer::normalize($title);
        if ($normalizedTitle === '') {
            return [];
        }

        return $this->byTypeAndNormalizedTitle[$type->value][$normalizedTitle] ?? [];
    }

    /**
     * @return list<StoryObject>
     */
    public function matchingExactly(string $title): array
    {
        $exactTitle = MiroTitleNormalizer::exact($title);
        if ($exactTitle === '') {
            return [];
        }

        return $this->byExactTitle[$exactTitle] ?? [];
    }
}
