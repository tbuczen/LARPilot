<?php

declare(strict_types=1);

namespace Tests\Functional\Public;

use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\FunctionalTester;

class LarpDetailsApplicationCardCest
{
    public function anonymousVisitorSeesOnlyLoginPrompt(FunctionalTester $I): void
    {
        $larp = LarpFactory::new()->inquiries()->create();

        $I->amOnRoute('public_larp_details', ['slug' => $larp->getSlug()]);

        $I->seeResponseCodeIsSuccessful();
        $I->see('You need to be logged in to apply', 'h5');
        $I->dontSeeElement('//h5[contains(., "logged in to apply")]/following-sibling::p');
        $I->dontSeeElement('i.fa-3x.bi-box-arrow-in-right');
        $I->seeElement('a.btn-primary > i.bi-box-arrow-in-right');
    }
}
