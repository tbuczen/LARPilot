<?php

declare(strict_types=1);

namespace Functional\Application\Controller;

use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Application\LarpApplicationChoiceFactory;
use Tests\Support\Factory\Application\LarpApplicationFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\Factory\StoryObject\CharacterFactory;
use Tests\Support\FunctionalTester;

class CharacterApplicationsControllerCest
{
    public function matchPageAccessDeniedForNonOrganizers(FunctionalTester $I): void
    {
        $I->wantTo('verify that match page is not accessible to non-organizers');

        // Create test data using factories
        $creator = UserFactory::createApprovedUser();
        $user = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($creator);
        $character = CharacterFactory::new()
            ->forLarp($larp)
            ->create()
            ->_real();

        $application = LarpApplicationFactory::new()
            ->forLarp($larp)
            ->forUser($user)
            ->create()
            ->_real();

        LarpApplicationChoiceFactory::new()
            ->forApplication($application)
            ->forCharacter($character)
            ->topPriority()
            ->create();

        $I->amLoggedInAs($user);

        // Request match page
        $I->amOnRoute('backoffice_larp_applications_match', ['larp' => $larp->getId()]);

        // Assert access is denied
        $I->seeResponseCodeIs(403);
        //TODO: Add test for larp organizer with proper permissions
    }

    public function applicantCantSeeBackofficeApplicationsPage(FunctionalTester $I): void
    {
        $I->wantTo('verify that applicants cannot see backoffice applications page');

        // Create test data using factories
        $user = UserFactory::createApprovedUser();
        $creator = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($creator);
        $character = CharacterFactory::new()
            ->forLarp($larp)
            ->create()
            ->_real();

        $application = LarpApplicationFactory::new()
            ->forLarp($larp)
            ->forUser($user)
            ->create()
            ->_real();

        LarpApplicationChoiceFactory::new()
            ->forApplication($application)
            ->forCharacter($character)
            ->topPriority()
            ->create();

        // Login as user
        $I->amLoggedInAs($user);
        $I->amOnRoute('backoffice_larp_applications_list', ['larp' => $larp->getId()]);
        $I->seeResponseCodeIs(403);

        $I->amLoggedInAs($creator);
        $I->amOnRoute('backoffice_larp_applications_list', ['larp' => $larp->getId()]);
        $I->seeResponseCodeIs(200);
    }
}
