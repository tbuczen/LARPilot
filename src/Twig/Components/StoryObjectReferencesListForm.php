<?php

namespace App\Twig\Components;

use App\Entity\StoryObject\StoryObject;
use App\Repository\ExternalReferenceRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class StoryObjectReferencesListForm
{
    use DefaultActionTrait;

    public function __construct(
        private readonly ExternalReferenceRepository $externalReferenceRepository,
    ) {
    }

    #[LiveProp]
    public StoryObject $storyObject;

    #[LiveProp(writable: true)]
    public int $additionalForms = 0;

    public function getExistingReferences(): array
    {
        return $this->externalReferenceRepository->findBy([
            'storyObject' => $this->storyObject,
        ]);
    }

    #[LiveAction]
    public function addForm(): void
    {
        $this->additionalForms++;
    }
}
