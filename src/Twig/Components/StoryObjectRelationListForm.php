<?php

namespace App\Twig\Components;

use App\Domain\StoryObject\Entity\StoryObject;
use App\Domain\StoryObject\Repository\RelationRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class StoryObjectRelationListForm
{
    use DefaultActionTrait;

    public function __construct(
        private readonly RelationRepository $relationRepository,
    ) {
    }

    #[LiveProp]
    public StoryObject $storyObject;

    #[LiveProp(writable: true)]
    public int $additionalForms = 0;

    public function getExistingRelations(): array
    {
        return $this->relationRepository->findBy([
            'larp' => $this->storyObject->getLarp(),
            'from' => $this->storyObject,
        ]);
    }

    #[LiveAction]
    public function addForm(): void
    {
        $this->additionalForms++;
    }
}
