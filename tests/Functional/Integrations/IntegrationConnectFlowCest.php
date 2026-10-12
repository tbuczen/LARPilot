<?php

declare(strict_types=1);

namespace Tests\Functional\Integrations;

use App\Domain\Integrations\Entity\Enum\ConnectionType;
use App\Domain\Integrations\Entity\Enum\IntegrationStatus;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\LarpIntegration;
use Symfony\Component\Uid\Uuid;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\Factory\Core\LarpParticipantFactory;
use Tests\Support\Factory\Integrations\LarpIntegrationFactory;
use Tests\Support\FunctionalTester;
use Tests\Support\Integrations\FakeGoogleOAuthGateway;

class IntegrationConnectFlowCest
{
    private const GOOGLE_CALLBACK_PATH = '/backoffice/larp/integration/connect/integration_google_drive/check';

    public function connectRedirectsToGoogleWithTheRegisteredCallbackUrl(FunctionalTester $I): void
    {
        $I->wantTo('start connecting Google Drive and keep the redirect URI registered in Google Cloud');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $I->amLoggedInAs($organizer);
        $authorizationQuery = $this->startGoogleConnect($I, $larp->getId());

        $I->assertSame('offline', $authorizationQuery['access_type']);
        $I->assertSame('consent', $authorizationQuery['prompt']);
        $I->assertSame(self::GOOGLE_CALLBACK_PATH, parse_url($authorizationQuery['redirect_uri'], PHP_URL_PATH));
        $I->assertSame($I->getUrl('backoffice_larp_connect_integration_check', [
            'provider' => LarpIntegrationProvider::Google->value,
        ]), self::GOOGLE_CALLBACK_PATH);
        $I->assertNotEmpty($authorizationQuery['state']);
        $I->assertStringNotContainsString($larp->getId()->toRfc4122(), $authorizationQuery['state']);
    }

    public function callbackStoresTheGoogleIntegration(FunctionalTester $I): void
    {
        $I->wantTo('finish connecting Google Drive and store the tokens on the LARP');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $I->amLoggedInAs($organizer);
        $state = $this->startGoogleConnect($I, $larp->getId())['state'];
        $this->visitGoogleCallback($I, ['state' => $state, 'code' => 'first-code']);

        $I->seeResponseCodeIsRedirection();
        $I->assertSame(
            $I->getUrl('backoffice_larp_integration_settings', ['larp' => $larp->getId()->toRfc4122()]),
            $I->grabResponse()->headers->get('Location')
        );

        $storedIntegration = $this->findGoogleIntegration($I, $larp->getId());
        $I->assertNotNull($storedIntegration);
        $I->assertSame(FakeGoogleOAuthGateway::accessTokenFor('first-code'), $storedIntegration->getAccessToken());
        $I->assertSame(FakeGoogleOAuthGateway::refreshTokenFor('first-code'), $storedIntegration->getRefreshToken());
        $I->assertSame(FakeGoogleOAuthGateway::ACCOUNT_EMAIL, $storedIntegration->getExternalAccountId());
        $I->assertSame(ConnectionType::UserOauth, $storedIntegration->getConnectionType());
        $I->assertSame(IntegrationStatus::Active, $storedIntegration->getStatus());

        $I->followRedirect();
        $I->see('Google Drive is now connected.');
    }

    public function reconnectingReplacesTokensOnTheExistingRow(FunctionalTester $I): void
    {
        $I->wantTo('reconnect Google Drive through the callback and keep one integration row');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $existingIntegration = LarpIntegrationFactory::new()
            ->forLarp($larp)
            ->createdBy($organizer)
            ->google()
            ->expired()
            ->needsReauth()
            ->create();

        $I->amLoggedInAs($organizer);
        $state = $this->startGoogleConnect($I, $larp->getId())['state'];
        $this->visitGoogleCallback($I, ['state' => $state, 'code' => 'reconnect-code']);

        $I->seeResponseCodeIsRedirection();
        $I->getEntityManager()->clear();
        $googleIntegrations = $I->getEntityManager()->getRepository(LarpIntegration::class)->findBy([
            'larp' => $larp->getId(),
            'provider' => LarpIntegrationProvider::Google,
        ]);
        $I->assertCount(1, $googleIntegrations);
        $I->assertEquals($existingIntegration->getId(), $googleIntegrations[0]->getId());
        $I->assertSame(FakeGoogleOAuthGateway::accessTokenFor('reconnect-code'), $googleIntegrations[0]->getAccessToken());
        $I->assertSame(IntegrationStatus::Active, $googleIntegrations[0]->getStatus());
        $I->assertNull($googleIntegrations[0]->getLastError());
    }

    public function twoTabsConnectTheirOwnLarps(FunctionalTester $I): void
    {
        $I->wantTo('connect two LARPs from two open tabs without one callback landing on the other LARP');

        $organizer = UserFactory::createApprovedUser();
        $firstLarp = LarpFactory::createDraftLarp($organizer);
        $secondLarp = LarpFactory::createDraftLarp($organizer);

        $I->amLoggedInAs($organizer);
        $firstState = $this->startGoogleConnect($I, $firstLarp->getId())['state'];
        $secondState = $this->startGoogleConnect($I, $secondLarp->getId())['state'];

        $this->visitGoogleCallback($I, ['state' => $firstState, 'code' => 'first-tab-code']);
        $I->seeResponseCodeIsRedirection();
        $this->visitGoogleCallback($I, ['state' => $secondState, 'code' => 'second-tab-code']);
        $I->seeResponseCodeIsRedirection();

        $I->assertSame(
            FakeGoogleOAuthGateway::accessTokenFor('first-tab-code'),
            $this->findGoogleIntegration($I, $firstLarp->getId())?->getAccessToken()
        );
        $I->assertSame(
            FakeGoogleOAuthGateway::accessTokenFor('second-tab-code'),
            $this->findGoogleIntegration($I, $secondLarp->getId())?->getAccessToken()
        );
    }

    public function reusedStateIsRejected(FunctionalTester $I): void
    {
        $I->wantTo('verify a connect state cannot be replayed');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $I->amLoggedInAs($organizer);
        $state = $this->startGoogleConnect($I, $larp->getId())['state'];
        $this->visitGoogleCallback($I, ['state' => $state, 'code' => 'first-code']);
        $I->seeResponseCodeIsRedirection();

        $this->visitGoogleCallback($I, ['state' => $state, 'code' => 'replayed-code']);

        $I->seeResponseCodeIs(400);
        $I->assertSame(FakeGoogleOAuthGateway::accessTokenFor('first-code'), $this->findGoogleIntegration($I, $larp->getId())?->getAccessToken());
    }

    public function forgedStateIsRejected(FunctionalTester $I): void
    {
        $I->wantTo('verify a forged connect state pointing at another LARP is rejected');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $victimOrganizer = UserFactory::createApprovedUser();
        $victimLarp = LarpFactory::createDraftLarp($victimOrganizer);

        $I->amLoggedInAs($organizer);
        $state = $this->startGoogleConnect($I, $larp->getId())['state'];
        [$payload, $signature] = explode('.', $state);
        $claims = json_decode((string) base64_decode(strtr($payload, '-_', '+/')), true);
        $claims['larp'] = $victimLarp->getId()->toRfc4122();
        $forgedPayload = rtrim(strtr(base64_encode((string) json_encode($claims)), '+/', '-_'), '=');

        $this->visitGoogleCallback($I, ['state' => $forgedPayload . '.' . $signature, 'code' => 'forged-code']);

        $I->seeResponseCodeIs(400);
        $I->assertNull($this->findGoogleIntegration($I, $victimLarp->getId()));
        $I->assertNull($this->findGoogleIntegration($I, $larp->getId()));
    }

    public function callbackWithoutStateIsRejected(FunctionalTester $I): void
    {
        $I->wantTo('verify the callback refuses a request without a connect state');

        $organizer = UserFactory::createApprovedUser();
        LarpFactory::createDraftLarp($organizer);

        $I->amLoggedInAs($organizer);
        $I->stopFollowingRedirects();
        $I->amOnPage(self::GOOGLE_CALLBACK_PATH . '?code=orphan-code');

        $I->seeResponseCodeIs(400);
    }

    public function cancelledConsentShowsAFlashInsteadOfAnError(FunctionalTester $I): void
    {
        $I->wantTo('cancel the Google consent screen and land back on the settings page with a message');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $I->amLoggedInAs($organizer);
        $state = $this->startGoogleConnect($I, $larp->getId())['state'];
        $this->visitGoogleCallback($I, ['state' => $state, 'error' => 'access_denied']);

        $I->seeResponseCodeIsRedirection();
        $I->followRedirect();
        $I->seeResponseCodeIsSuccessful();
        $I->see('Connecting Google Drive was cancelled. Nothing was changed.');
        $I->assertNull($this->findGoogleIntegration($I, $larp->getId()));
    }

    public function rejectedAuthorizationCodeShowsAFlash(FunctionalTester $I): void
    {
        $I->wantTo('see a message instead of an error page when Google rejects the authorization code');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $I->amLoggedInAs($organizer);
        $state = $this->startGoogleConnect($I, $larp->getId())['state'];
        $this->visitGoogleCallback($I, ['state' => $state, 'code' => FakeGoogleOAuthGateway::REJECTED_CODE]);

        $I->seeResponseCodeIsRedirection();
        $I->followRedirect();
        $I->see('Could not connect Google Drive. Please try again.');
        $I->assertNull($this->findGoogleIntegration($I, $larp->getId()));
    }

    public function storyWriterCannotStartConnecting(FunctionalTester $I): void
    {
        $I->wantTo('verify a story writer cannot start connecting an integration');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $storyWriter = UserFactory::createApprovedUser();
        LarpParticipantFactory::new()->forLarp($larp)->forUser($storyWriter)->storyWriter()->create();

        $I->amLoggedInAs($storyWriter);
        $I->stopFollowingRedirects();
        $I->amOnRoute('backoffice_larp_connect_integration', [
            'larp' => $larp->getId(),
            'provider' => LarpIntegrationProvider::Google->value,
        ]);

        $I->seeResponseCodeIs(403);
    }

    public function providerWithoutConnectCapabilityCannotBeConnected(FunctionalTester $I): void
    {
        $I->wantTo('verify connecting Facebook is not found instead of crashing');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $I->amLoggedInAs($organizer);
        $I->stopFollowingRedirects();
        $I->amOnRoute('backoffice_larp_connect_integration', [
            'larp' => $larp->getId(),
            'provider' => LarpIntegrationProvider::Facebook->value,
        ]);

        $I->seeResponseCodeIs(404);
    }

    public function settingsPageHidesProvidersWithoutConnectCapability(FunctionalTester $I): void
    {
        $I->wantTo('see a Connect button only for providers that can be connected');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integration_settings', ['larp' => $larp->getId()]);

        $I->seeResponseCodeIsSuccessful();
        foreach ([LarpIntegrationProvider::Google, LarpIntegrationProvider::Discord] as $connectableProvider) {
            $I->seeElement(sprintf('a[href="%s"]', $this->connectUrl($I, $larp->getId(), $connectableProvider)));
        }
        foreach ([LarpIntegrationProvider::Facebook, LarpIntegrationProvider::Miro] as $hiddenProvider) {
            $I->dontSeeElement(sprintf('a[href="%s"]', $this->connectUrl($I, $larp->getId(), $hiddenProvider)));
        }
        $I->dontSee('Facebook', 'h2');
    }

    /**
     * @return array<string, string>
     */
    private function startGoogleConnect(FunctionalTester $I, Uuid $larpId): array
    {
        $I->stopFollowingRedirects();
        $I->amOnPage($this->connectUrl($I, $larpId, LarpIntegrationProvider::Google));
        $I->seeResponseCodeIsRedirection();

        $authorizationUrl = (string) $I->grabResponse()->headers->get('Location');
        $I->assertStringStartsWith(FakeGoogleOAuthGateway::AUTHORIZATION_ENDPOINT . '?', $authorizationUrl);
        parse_str((string) parse_url($authorizationUrl, PHP_URL_QUERY), $authorizationQuery);

        return $authorizationQuery;
    }

    /**
     * @param array<string, string> $query
     */
    private function visitGoogleCallback(FunctionalTester $I, array $query): void
    {
        $I->stopFollowingRedirects();
        $I->amOnPage(self::GOOGLE_CALLBACK_PATH . '?' . http_build_query($query));
    }

    private function connectUrl(FunctionalTester $I, Uuid $larpId, LarpIntegrationProvider $provider): string
    {
        return $I->getUrl('backoffice_larp_connect_integration', [
            'larp' => $larpId->toRfc4122(),
            'provider' => $provider->value,
        ]);
    }

    private function findGoogleIntegration(FunctionalTester $I, Uuid $larpId): ?LarpIntegration
    {
        $entityManager = $I->getEntityManager();
        $entityManager->clear();

        return $entityManager->getRepository(LarpIntegration::class)->findOneBy([
            'larp' => $larpId,
            'provider' => LarpIntegrationProvider::Google,
        ]);
    }
}
