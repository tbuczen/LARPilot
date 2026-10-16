<?php

declare(strict_types=1);

namespace Tests\Functional\Larp;

use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\FunctionalTester;

class LarpStatusPageCest
{
    public function organizerSeesWorkflowWithBootstrapIconsAndBadges(FunctionalTester $I): void
    {
        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_status_index', ['larp' => $larp->getId()]);

        $I->seeResponseCodeIsSuccessful();
        $I->seeElement('.workflow-step.active .badge.text-bg-primary');
        $I->seeElement('.workflow-arrow i.bi-arrow-down');
        $I->seeElement('#publishRequirements i.bi-check-circle-fill, #publishRequirements i.bi-x-circle-fill');
        $I->dontSeeElement('i.fas');
        $I->dontSeeElement('[class*="badge-"]');
    }
}
