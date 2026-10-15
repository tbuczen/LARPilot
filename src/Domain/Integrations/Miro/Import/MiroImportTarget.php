<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Miro\Import;

use App\Domain\StoryObject\Entity\Enum\TargetType;
use App\Domain\StoryObject\Entity\StoryObject;

final readonly class MiroImportTarget
{
    public function __construct(
        public TargetType $type,
        public string $title,
        public ?StoryObject $storyObject = null,
    ) {
    }

    public static function existing(StoryObject $storyObject): self
    {
        return new self($storyObject::getTargetType(), (string) $storyObject->getTitle(), $storyObject);
    }

    public static function planned(TargetType $type, string $title): self
    {
        return new self($type, $title);
    }

    public function isPlanned(): bool
    {
        return $this->storyObject === null;
    }

    public function key(): string
    {
        if ($this->storyObject instanceof StoryObject) {
            return $this->storyObject->getId()->toRfc4122();
        }

        return $this->type->value . ':' . MiroTitleNormalizer::normalize($this->title);
    }
}
