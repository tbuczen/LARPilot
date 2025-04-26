<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Attribute\Route;
class PublicCommonController extends AbstractController
{
    #[Route('/switch-locale/{_locale}', name: 'switch_locale')]
    public function switchLocale(Request $request, string $_locale): RedirectResponse
    {
        // List of allowed locales
        $allowedLocales = ['en', 'pl', 'de', 'es', 'cz', 'sl', 'it', 'no', 'sv'];
        if (!in_array($_locale, $allowedLocales, true)) {
            $_locale = 'en';
        }

        // Set the locale in the session
        $request->getSession()->set('_locale', $_locale);

        // Redirect back to the referring page, or to homepage if none is set
        $referer = $request->headers->get('referer', '/');
        return $this->redirect($referer);
    }

    #[Route('/terms', name: 'terms')]
    public function terms(): Response
    {
        return $this->render('public/terms.html.twig');
    }

    #[Route('/connect', name: 'sso_connect')]
    public function index(Request $request, Session $session): Response
    {
        $redirectTo = $request->query->get('redirectTo');

        if ($redirectTo) {
            $session->set('redirect_to_after_login', $redirectTo);
        }

        return $this->render('sso/index.html.twig');
    }
}