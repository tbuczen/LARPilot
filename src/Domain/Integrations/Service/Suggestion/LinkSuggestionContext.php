<?php

namespace App\Domain\Integrations\Service\Suggestion;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\Tag;
use App\Domain\Core\Repository\TagRepository;
use App\Domain\StoryObject\Entity\StoryObject;
use App\Domain\StoryObject\Repository\StoryObjectRepository;

final class LinkSuggestionContext
{
    /** @var list<StoryObject>|null */
    private ?array $storyObjects = null;

    /** @var array<string, Tag>|null */
    private ?array $tagsById = null;

    public function __construct(
        public readonly Larp $larp,
        private readonly StoryObjectRepository $storyObjectRepository,
        private readonly TagRepository $tagRepository,
    ) {
    }

    /**
     * @return list<StoryObject>
     */
    public function storyObjects(): array
    {
        return $this->storyObjects ??= array_values($this->storyObjectRepository->findBy(['larp' => $this->larp]));
    }

    public function tag(string $tagId): ?Tag
    {
        if ($this->tagsById === null) {
            $this->tagsById = [];
            foreach ($this->tagRepository->findBy(['larp' => $this->larp]) as $tag) {
                $this->tagsById[$tag->getId()->toRfc4122()] = $tag;
            }
        }

        return $this->tagsById[$tagId] ?? null;
    }
}
