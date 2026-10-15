<?php

declare(strict_types=1);

namespace Tests\Functional\Integrations\Google;

use App\Domain\Integrations\Entity\Enum\IntegrationStatus;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Service\Google\GoogleIntegrationService;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\Factory\Integrations\LarpIntegrationFactory;
use Tests\Support\FunctionalTester;
use Tests\Support\Integrations\FakeGoogleOAuthGateway;

class GoogleDriveScopeCest
{
    private const FULL_DRIVE_GRANT = 'https://www.googleapis.com/auth/drive email';

    public function connectRequestsOnlyTheDriveFileScope(FunctionalTester $I): void
    {
        $I->wantTo('ask Google only for the drive.file scope when connecting');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $I->amLoggedInAs($organizer);
        $requestedScopes = explode(' ', $this->startGoogleConnect($I, $larp->getId()->toRfc4122())['scope']);

        $I->assertContains(GoogleIntegrationService::DRIVE_FILE_SCOPE, $requestedScopes);
        $I->assertNotContains('https://www.googleapis.com/auth/drive', $requestedScopes);
    }

    public function integrationGrantedTheOldScopeShowsAReconnectPrompt(FunctionalTester $I): void
    {
        $I->wantTo('see a reconnect prompt instead of the file picker for a full-drive Google grant');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $integration = LarpIntegrationFactory::new()->forLarp($larp)->createdBy($organizer)->google()
            ->with(['scopes' => self::FULL_DRIVE_GRANT])
            ->create()
            ->_real();

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integrations_mappings', ['larp' => $larp->getId(), 'provider' => LarpIntegrationProvider::Google->value]);

        $I->seeResponseCodeIsSuccessful();
        $I->seeElement('[data-google-reconnect-prompt]');
        $I->dontSeeElement('[data-controller="google-file-picker"]');
        $I->seeElement(sprintf('a[href="%s"]', $I->getUrl('backoffice_larp_connect_integration', [
            'larp' => $larp->getId(),
            'provider' => LarpIntegrationProvider::Google->value,
        ])));

        /** @var GoogleIntegrationService $googleIntegrationService */
        $googleIntegrationService = $I->grabService(GoogleIntegrationService::class);
        $health = $googleIntegrationService->health($integration);
        $I->assertSame(IntegrationStatus::NeedsReauth, $health->status);
        $I->assertSame(GoogleIntegrationService::SCOPE_UPGRADE_MESSAGE, $health->message);
    }

    public function integrationGrantedDriveFileShowsThePicker(FunctionalTester $I): void
    {
        $I->wantTo('see the file picker without a reconnect prompt for a drive.file Google grant');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        LarpIntegrationFactory::new()->forLarp($larp)->createdBy($organizer)->google()->create();

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integrations_mappings', ['larp' => $larp->getId(), 'provider' => LarpIntegrationProvider::Google->value]);

        $I->seeResponseCodeIsSuccessful();
        $I->dontSeeElement('[data-google-reconnect-prompt]');
        $I->seeElement('[data-controller="google-file-picker"]');
    }

    public function reconnectingReplacesTheOldScope(FunctionalTester $I): void
    {
        $I->wantTo('reconnect a full-drive Google grant and end up with drive.file');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        LarpIntegrationFactory::new()->forLarp($larp)->createdBy($organizer)->google()
            ->with(['scopes' => self::FULL_DRIVE_GRANT])
            ->create();

        $I->amLoggedInAs($organizer);
        $state = $this->startGoogleConnect($I, $larp->getId()->toRfc4122())['state'];
        $I->amOnPage($I->getUrl('backoffice_larp_connect_integration_check', [
            'provider' => LarpIntegrationProvider::Google->value,
            'state' => $state,
            'code' => 'upgrade-code',
        ]));
        $I->startFollowingRedirects();

        $entityManager = $I->getEntityManager();
        $entityManager->clear();
        $integration = $entityManager->getRepository(LarpIntegration::class)->findOneBy([
            'larp' => $larp->getId(),
            'provider' => LarpIntegrationProvider::Google,
        ]);
        $I->assertInstanceOf(LarpIntegration::class, $integration);
        $I->assertSame(FakeGoogleOAuthGateway::GRANTED_SCOPE, $integration->getScopes());
        $I->assertFalse(GoogleIntegrationService::requiresScopeUpgrade($integration));

        $I->amOnRoute('backoffice_larp_integrations_mappings', ['larp' => $larp->getId(), 'provider' => LarpIntegrationProvider::Google->value]);
        $I->dontSeeElement('[data-google-reconnect-prompt]');
    }

    /**
     * @return array<string, string>
     */
    private function startGoogleConnect(FunctionalTester $I, string $larpId): array
    {
        $I->stopFollowingRedirects();
        $I->amOnRoute('backoffice_larp_connect_integration', [
            'larp' => $larpId,
            'provider' => LarpIntegrationProvider::Google->value,
        ]);
        $I->seeResponseCodeIsRedirection();

        parse_str((string) parse_url((string) $I->grabResponse()->headers->get('Location'), PHP_URL_QUERY), $authorizationQuery);

        return $authorizationQuery;
    }
}
