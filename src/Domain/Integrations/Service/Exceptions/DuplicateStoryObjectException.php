<?php

namespace App\Domain\Integrations\Service\Exceptions;

use App\Domain\StoryObject\Entity\StoryObject;

class DuplicateStoryObjectException extends \RuntimeException
{
    public function __construct(StoryObject $storyObject, string $externalUrl)
    {
        parent::__construct(sprintf('Object "%s (%s)" already exists: %s', $storyObject::getTargetType()->value, $storyObject->getId(), $externalUrl));
    }
}
