<?php

namespace App\Domain\Core\EventSubscriber;

use App\Domain\Account\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Translation\LocaleAwareInterface;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 0)]
readonly class LocaleListener
{
    public function __construct(
        private Security $security,
        private LocaleAwareInterface $translator,
        private string $defaultLocale = 'en',
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        $user = $this->security->getUser();
        $request = $event->getRequest();

        $locale =
            $request->attributes->get('_locale') ??
            ($user instanceof User ? $user->getPreferredLocale()?->value : null) ??
            $this->defaultLocale;

        $this->setLocale($request, $locale);
    }

    private function setLocale(Request $request, string $locale): void
    {
        $request->setLocale($locale);
        $this->translator->setLocale($locale);
    }
}
