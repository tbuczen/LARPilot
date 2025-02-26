<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 20)]
readonly class LocaleListener
{

    public function __construct(private string $defaultLocale = 'en') {}

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$request->hasPreviousSession()) {
            return;
        }

        if ($locale = $request->attributes->get('_locale')) {
            $request->getSession()->set('_locale', $locale);
        } else {
            // Otherwise, use the locale from the session if it exists, otherwise fallback
            $request->setLocale($request->getSession()->get('_locale', $this->defaultLocale));
        }
    }

}
