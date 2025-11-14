<?php

namespace App\Security\EventListener;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Repository\LarpRepository;
use App\Domain\Core\Security\Voter\LarpDetailsVoter;
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
        if (!$route || !str_starts_with((string) $route, 'backoffice_larp_')) {
            return;
        }

        // Routes that don't require LARP-specific permissions (whitelisted)
        $whitelistedRoutes = [
            'backoffice_larp_list',
            'backoffice_larp_create',
            'backoffice_larp_connect_integration_check', // OAuth callback route without larp param
        ];

        if (in_array($route, $whitelistedRoutes, true)) {
            return;
        }

        // All other backoffice_larp_* routes MUST have a {larp} parameter
        $larpId = $request->attributes->get('larp');
        $larp = $this->larpRepository->find($larpId);

        if (!$larp instanceof Larp) {
            // If route starts with backoffice_larp_ but has no valid larp param, it's a security issue
            throw new AccessDeniedHttpException('Invalid or missing LARP parameter.');
        }

        // Apply the security check - user must be an organizer of this specific LARP
        if (!$this->authorizationChecker->isGranted(LarpDetailsVoter::VIEW, $larp)) {
            throw new AccessDeniedHttpException('Access denied to LARP backoffice.');
        }
    }
}
