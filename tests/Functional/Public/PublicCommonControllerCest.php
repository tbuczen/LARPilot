<?php

declare(strict_types=1);

namespace Tests\Functional\Public;

use Tests\Support\FunctionalTester;

class PublicCommonControllerCest
{
    public function localeCanBeSwitched(FunctionalTester $I): void
    {
        $I->wantTo('verify that locale can be switched via switch-locale route');

        // First request: Switch locale to German
        // This redirects back to referer (/) and sets _locale in session
        $I->stopFollowingRedirects();
        $I->amOnPage('/switch-locale/de');
        $I->seeResponseCodeIsRedirection();

        // Follow the redirect to verify the page loads correctly
        $I->followRedirect();
        $I->seeResponseCodeIsSuccessful();
    }

    public function localeRouteReturnsAllowedLocales(FunctionalTester $I): void
    {
        $I->wantTo('verify that switch-locale route accepts valid locales');

        $allowedLocales = ['en', 'pl', 'de', 'es', 'cz', 'sl', 'it', 'no', 'sv'];

        $I->stopFollowingRedirects();
        foreach ($allowedLocales as $locale) {
            $I->amOnPage('/switch-locale/' . $locale);
            $I->seeResponseCodeIsRedirection();
        }
    }

    public function localeRouteHandlesInvalidLocale(FunctionalTester $I): void
    {
        $I->wantTo('verify that switch-locale route handles invalid locales gracefully');

        // Invalid locale should default to 'en' and still redirect
        $I->stopFollowingRedirects();
        $I->amOnPage('/switch-locale/invalid');
        $I->seeResponseCodeIsRedirection();
    }
}
