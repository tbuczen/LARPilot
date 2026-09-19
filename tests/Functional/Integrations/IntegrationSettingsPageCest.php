<?php

declare(strict_types=1);

namespace Tests\Functional\Integrations;

use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\LarpIntegration;
use Doctrine\Common\Collections\ArrayCollection;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
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

        $this->persistIntegration($I, $larp, $organizer, LarpIntegrationProvider::Google, '-1 day');

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integration_settings', ['larp' => $larp->getId()]);
        $I->seeResponseCodeIsSuccessful();
    }

    public function pageRendersWithFacebookIntegration(FunctionalTester $I): void
    {
        $I->wantTo('render the integration settings page when a Facebook integration exists');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $this->persistIntegration($I, $larp, $organizer, LarpIntegrationProvider::Facebook, '+1 day');

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integration_settings', ['larp' => $larp->getId()]);
        $I->seeResponseCodeIsSuccessful();
    }

    private function persistIntegration(
        FunctionalTester $I,
        mixed $larp,
        mixed $creator,
        LarpIntegrationProvider $provider,
        string $expiryModifier,
    ): void {
        $integration = new LarpIntegration();
        $integration->setProvider($provider);
        $integration->setAccessToken('test-access-token');
        $integration->setRefreshToken('test-refresh-token');
        $integration->setExpiresAt(new \DateTimeImmutable($expiryModifier));
        $integration->setScopes('https://www.googleapis.com/auth/drive.file');
        $integration->setLarp($larp->_real());
        $integration->setCreatedBy($creator);

        $reflection = new \ReflectionProperty(LarpIntegration::class, 'sharedFiles');
        $reflection->setValue($integration, new ArrayCollection());

        $entityManager = $I->getEntityManager();
        $entityManager->persist($integration);
        $entityManager->flush();
    }
}
