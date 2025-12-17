<?php

declare(strict_types=1);

namespace Tests\Functional\StoryMarketplace;

use Tests\Support\FunctionalTester;

class RecruitmentControllerCest
{
    public function _before(FunctionalTester $I): void
    {
        $I->stopFollowingRedirects();
    }

    public function recruitmentRouteRequiresAuthentication(FunctionalTester $I): void
    {
        $I->wantTo('verify that recruitment route requires authentication');

        $I->amOnPage('/backoffice/larp/00000000-0000-0000-0000-000000000000/story/thread/123/recruitment');

        // Unauthenticated users should be redirected or receive a client error
        $I->seeResponseCodeIsRedirection();
    }
}
