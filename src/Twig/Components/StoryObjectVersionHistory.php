<?php

namespace App\Twig\Components;

use App\Entity\StoryObject\StoryObject;
use App\Service\StoryObject\StoryObjectVersionService;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class StoryObjectVersionHistory
{
    use DefaultActionTrait;

    public function __construct(private readonly StoryObjectVersionService $service)
    {
    }

    #[LiveProp]
    public StoryObject $storyObject;

    public function getHistory(): array
    {
        return $this->service->getVersionHistory($this->storyObject);
    }
}
