<?php

declare(strict_types=1);

namespace App\Domain\StoryObject\DTO;

use App\Domain\StoryObject\Entity\StoryObject;

/**
 * Data Transfer Object representing a mention of a StoryObject in another StoryObject.
 * Used to display where and how a StoryObject is referenced across the story.
 */
class MentionDTO
{
    public function __construct(
        private readonly StoryObject $sourceObject,
        private readonly string $mentionType,
        private readonly string $context,
        private readonly ?string $fieldName = null,
    ) {
    }

    public function getSourceObject(): StoryObject
    {
        return $this->sourceObject;
    }

    public function getMentionType(): string
    {
        return $this->mentionType;
    }

    public function getContext(): string
    {
        return $this->context;
    }

    public function getFieldName(): ?string
    {
        return $this->fieldName;
    }
}
