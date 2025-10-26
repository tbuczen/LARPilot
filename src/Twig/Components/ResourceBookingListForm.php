<?php

namespace App\Twig\Components;

use App\Domain\EventPlanning\Entity\ScheduledEvent;
use App\Domain\EventPlanning\Repository\ResourceBookingRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class ResourceBookingListForm
{
    use DefaultActionTrait;

    public function __construct(
        private readonly ResourceBookingRepository $resourceBookingRepository,
    ) {
    }

    #[LiveProp]
    public ScheduledEvent $scheduledEvent;

    #[LiveProp(writable: true)]
    public int $additionalForms = 0;

    public function getExistingBookings(): array
    {
        return $this->resourceBookingRepository->findBy([
            'scheduledEvent' => $this->scheduledEvent,
        ]);
    }

    #[LiveAction]
    public function addForm(): void
    {
        $this->additionalForms++;
    }
}
