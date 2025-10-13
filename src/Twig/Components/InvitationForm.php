<?php

namespace App\Twig\Components;

use App\Domain\Core\Entity\LarpInvitation;
use App\Domain\Core\Form\InvitationType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class InvitationForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public ?LarpInvitation $formData = null;

    protected function instantiateForm(): FormInterface
    {
        $formData = $this->formData ?? new LarpInvitation();

        return $this->createForm(InvitationType::class, $formData);
    }
}
