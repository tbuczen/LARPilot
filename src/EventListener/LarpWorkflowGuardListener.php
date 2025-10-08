<?php

namespace App\EventListener;

use App\Entity\Larp;
use App\Service\Larp\Workflow\LarpTransitionGuardService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\GuardEvent;

class LarpWorkflowGuardListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly LarpTransitionGuardService $guardService
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.larp_stage_status.guard.to_published' => 'guardToPublished',
            'workflow.larp_stage_status.guard.to_inquiries' => 'guardToInquiries',
            'workflow.larp_stage_status.guard.to_confirmed' => 'guardToConfirmed',
        ];
    }

    public function guardToPublished(GuardEvent $event): void
    {
        /** @var Larp $larp */
        $larp = $event->getSubject();
        
        if (!$this->guardService->canPublish($larp)) {
            $event->setBlocked(true, 'LARP cannot be published: missing required information');
        }
    }

    public function guardToInquiries(GuardEvent $event): void
    {
        /** @var Larp $larp */
        $larp = $event->getSubject();
        
        if (!$this->guardService->canOpenForInquiries($larp)) {
            $event->setBlocked(true, 'LARP cannot be opened for inquiries: characters missing descriptions');
        }
    }

    public function guardToConfirmed(GuardEvent $event): void
    {
        /** @var Larp $larp */
        $larp = $event->getSubject();

        // Note: We don't block the transition here, we let it show up but disabled
        // The blocking is handled by the validation in the controller/service
        // This allows the button to appear with validation errors in the dropdown
    }
}
