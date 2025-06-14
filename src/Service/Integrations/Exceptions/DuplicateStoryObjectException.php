<?php

namespace App\Service\Integrations\Exceptions;

use App\Entity\StoryObject\StoryObject;

class DuplicateStoryObjectException extends \RuntimeException
{
    public function __construct(StoryObject $storyObject, string $externalUrl)
    {
        parent::__construct(sprintf('Object "%s (%s)" already exists: %s', $storyObject::getTargetType()->value, $storyObject->getId(), $externalUrl));
    }
}
