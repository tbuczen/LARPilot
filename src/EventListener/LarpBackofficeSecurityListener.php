<?php

namespace App\EventListener;

use App\Entity\Larp;
use App\Repository\LarpRepository;
use App\Security\Voter\Backoffice\Larp\LarpDetailsVoter;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[AsEventListener(event: 'kernel.controller')]
readonly class LarpBackofficeSecurityListener
{
    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker,
        private LarpRepository $larpRepository,
    ) {
    }

    public function __invoke(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        $route = $request->attributes->get('_route');
        // Check if this is a LARP backoffice route
        if (!$route || !str_starts_with($route, 'backoffice_larp_')) {
            return;
        }

        // Check if the route has a larp parameter
        $larpId = $request->attributes->get('larp');
        $larp = $this->larpRepository->find($larpId);
        if (!$larp instanceof Larp) {
            return;
        }

        // Apply the security check
        if (!$this->authorizationChecker->isGranted(LarpDetailsVoter::VIEW, $larp)) {
            throw new AccessDeniedHttpException('Access denied to LARP backoffice.');
        }
    }
}
