<?php

declare(strict_types=1);

namespace Tests\Functional\Public;

use Tests\Support\FunctionalTester;

class PublicCommonControllerCest
{
    public function localeCanBeSwitched(FunctionalTester $I): void
    {
        $I->wantTo('verify that locale can be switched via switch-locale route');

        $I->amOnPage('/switch-locale/de');
        $I->seeResponseCodeIs(302);
        $I->seeInSession('_locale', 'de');
    }
}
