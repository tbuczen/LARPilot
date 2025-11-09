<?php

namespace App\Domain\Account\EventSubscriber;

use App\Domain\Account\Entity\Enum\UserStatus;
use App\Domain\Account\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 9)]
readonly class UserStatusListener
{
    public function __construct(
        private Security $security,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        // Only check for backoffice routes
        if (!str_starts_with($route, 'backoffice_')) {
            return;
        }

        // Allow access to the pending approval page itself
        if ($route === 'backoffice_account_pending_approval') {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        // Check if user is not approved
        if (!$user->isApproved()) {
            $event->setResponse(
                new RedirectResponse(
                    $this->urlGenerator->generate('backoffice_account_pending_approval')
                )
            );
        }
    }
}
