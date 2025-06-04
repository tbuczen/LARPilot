<?php

namespace App\Twig\Components;


use App\Entity\ExternalReference;
use App\Entity\StoryObject\StoryObject;
use App\Form\ExternalReferenceType;
use App\Repository\ExternalReferenceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class StoryObjectReferenceForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public StoryObject $storyObject;

    #[LiveProp]
    public ?ExternalReference $initialFormData = null;

    #[LiveProp]
    public bool $saved = false;

    #[LiveAction]
    public function save(
        ExternalReferenceRepository $repo,
    ): void
    {
        $this->submitForm();
        /** @var ExternalReference $reference */
        $reference = $this->getForm()->getData();
        $reference->setStoryObject($this->storyObject);
        $repo->save($reference);
    }

    public function instantiateForm(): FormInterface
    {
        $formData = $this->initialFormData ?? (new ExternalReference())
            ->setStoryObject($this->storyObject)
            ->setStoryObjectType($this->storyObject->getTargetType());

        return $this->createForm(ExternalReferenceType::class, $formData, [
            'larp' => $this->storyObject->getLarp(),
        ]);
    }

    public function getFormName(): string
    {
        return 'form';
    }

}