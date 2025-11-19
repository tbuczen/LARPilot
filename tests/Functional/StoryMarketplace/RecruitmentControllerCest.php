<?php

declare(strict_types=1);

namespace Tests\Functional\StoryMarketplace;

use Tests\Support\FunctionalTester;

class RecruitmentControllerCest
{
    public function recruitmentRouteRequiresAuthentication(FunctionalTester $I): void
    {
        $I->wantTo('verify that recruitment route requires authentication');

        $I->amOnPage('/backoffice/larp/00000000-0000-0000-0000-000000000000/story/thread/123/recruitment');

        // Unauthenticated users should be redirected or receive a client error
        $responseCode = $I->grabResponseCode();
        $I->assertTrue(
            $responseCode >= 300 && $responseCode < 400 || $responseCode >= 400 && $responseCode < 500,
            'Response should be a redirect (3xx) or client error (4xx)'
        );
    }
}
