<?php

namespace App\Twig\Components;


use App\Entity\Enum\TargetType;
use App\Entity\StoryObject\Relation;
use App\Entity\StoryObject\StoryObject;
use App\Form\RelationType;
use App\Repository\StoryObject\RelationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class StoryObjectRelationForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public StoryObject $storyObject;

    #[LiveProp]
    public ?Relation $initialFormData = null;

    #[LiveProp]
    public bool $saved = false;

    #[LiveAction]
    public function save(
        RelationRepository $repo,
    ): void
    {
        $this->submitForm();
        /** @var Relation $relation */
        $relation = $this->getForm()->getData();
        $relation->setLarp($this->storyObject->getLarp());
        $repo->save($relation);
    }

    public function instantiateForm(): FormInterface
    {
        $formData = $this->initialFormData ?? (new Relation())
            ->setFromType(TargetType::Character)
            ->setFrom($this->storyObject);

        return $this->createForm(RelationType::class, $formData, [
            'larp' => $this->storyObject->getLarp(),
            'contextOwner' => $this->storyObject,
        ]);
    }

    public function getFormName(): string
    {
        return 'form';
    }

}