<?php

declare(strict_types=1);

namespace Tests\Functional\Integrations;

use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\Factory\Integrations\LarpIntegrationFactory;
use Tests\Support\FunctionalTester;

class IntegrationSettingsPageCest
{
    public function pageRendersWithNoIntegrations(FunctionalTester $I): void
    {
        $I->wantTo('render the integration settings page for a LARP with no integrations');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integration_settings', ['larp' => $larp->getId()]);
        $I->seeResponseCodeIsSuccessful();
    }

    public function pageRendersWithExpiredGoogleIntegration(FunctionalTester $I): void
    {
        $I->wantTo('render the integration settings page when the Google token has expired');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        LarpIntegrationFactory::new()->forLarp($larp)->createdBy($organizer)->google()->expired()->create();

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integration_settings', ['larp' => $larp->getId()]);
        $I->seeResponseCodeIsSuccessful();
    }

    public function pageRendersWithFacebookIntegration(FunctionalTester $I): void
    {
        $I->wantTo('render the integration settings page when a Facebook integration exists');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        LarpIntegrationFactory::new()->forLarp($larp)->createdBy($organizer)->facebook()->create();

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integration_settings', ['larp' => $larp->getId()]);
        $I->seeResponseCodeIsSuccessful();
    }
}
