<?php

namespace App\Twig\Components;

use App\Domain\EventPlanning\Entity\ResourceBooking;
use App\Domain\EventPlanning\Entity\ScheduledEvent;
use App\Domain\EventPlanning\Form\ResourceBookingType;
use App\Domain\EventPlanning\Repository\ResourceBookingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class ResourceBookingForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public ScheduledEvent $scheduledEvent;

    #[LiveProp]
    public ?ResourceBooking $initialFormData = null;

    #[LiveProp]
    public bool $saved = false;

    #[LiveAction]
    public function save(
        ResourceBookingRepository $repo,
    ): void {
        $this->submitForm();
        /** @var ResourceBooking $booking */
        $booking = $this->getForm()->getData();
        $booking->setScheduledEvent($this->scheduledEvent);
        $repo->save($booking);
        $this->saved = true;
    }

    #[LiveAction]
    public function delete(
        ResourceBookingRepository $repo,
    ): void {
        if ($this->initialFormData !== null) {
            $repo->remove($this->initialFormData);
        }
    }

    public function instantiateForm(): FormInterface
    {
        $formData = $this->initialFormData ?? new ResourceBooking();

        return $this->createForm(ResourceBookingType::class, $formData, [
            'larp' => $this->scheduledEvent->getLarp(),
        ]);
    }

    public function getFormName(): string
    {
        return 'form';
    }
}
