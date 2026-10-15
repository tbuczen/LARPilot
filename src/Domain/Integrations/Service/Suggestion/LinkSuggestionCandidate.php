<?php

namespace App\Domain\Integrations\Service\Suggestion;

use App\Domain\Core\Entity\Tag;
use App\Domain\StoryObject\Entity\Enum\TargetType;
use App\Domain\StoryObject\Entity\StoryObject;

final readonly class LinkSuggestionCandidate
{
    private function __construct(
        public string $source,
        public float $score,
        public ?StoryObject $storyObject = null,
        public ?TargetType $targetType = null,
        public ?Tag $tag = null,
    ) {
    }

    public static function link(string $source, float $score, StoryObject $storyObject): self
    {
        return new self($source, self::boundedScore($score), $storyObject);
    }

    public static function create(string $source, float $score, ?TargetType $targetType, ?Tag $tag = null): self
    {
        return new self($source, self::boundedScore($score), null, $targetType, $tag);
    }

    public static function keyFor(?StoryObject $storyObject, ?TargetType $targetType, ?Tag $tag): string
    {
        if ($storyObject instanceof StoryObject) {
            return 'story_object:' . $storyObject->getId()->toRfc4122();
        }

        return 'create:' . ($targetType->value ?? '') . ':' . ($tag?->getId()->toRfc4122() ?? '');
    }

    public function key(): string
    {
        return self::keyFor($this->storyObject, $this->targetType, $this->tag);
    }

    public function signature(): string
    {
        return sprintf('%s|%s|%.4F', $this->source, $this->key(), $this->score);
    }

    private static function boundedScore(float $score): float
    {
        return max(0.0, min(1.0, $score));
    }
}
