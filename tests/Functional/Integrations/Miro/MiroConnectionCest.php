<?php

declare(strict_types=1);

namespace Tests\Functional\Integrations\Miro;

use App\Domain\Core\Entity\Larp;
use App\Domain\Integrations\Entity\Enum\ConnectionType;
use App\Domain\Integrations\Entity\Enum\ExternalSourceKind;
use App\Domain\Integrations\Entity\Enum\IntegrationStatus;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\ExternalItem;
use App\Domain\Integrations\Entity\ExternalSource;
use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Miro\OAuth\MiroOAuthProvider;
use Symfony\Component\Uid\Uuid;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\Factory\Core\LarpParticipantFactory;
use Tests\Support\Factory\Integrations\ExternalItemFactory;
use Tests\Support\Factory\Integrations\ExternalSourceFactory;
use Tests\Support\Factory\Integrations\LarpIntegrationFactory;
use Tests\Support\FunctionalTester;
use Tests\Support\Http\IntegrationFixtureRouters;
use Tests\Support\Integrations\FakeMiroOAuthGateway;
use Tests\Support\Integrations\MiroTokenEndpoint;

class MiroConnectionCest
{
    private const MIRO_CALLBACK_PATH = '/backoffice/larp/integration/connect/integration_miro/check';
    private const SECOND_BOARD_ID = 'uXjVOFq2Hk8=';

    public function connectStoresTheMiroTeamOnTheLarp(FunctionalTester $I): void
    {
        $I->wantTo('connect Miro and store the tokens and the Miro team on the LARP');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $I->amLoggedInAs($organizer);
        $authorizationQuery = $this->startMiroConnect($I, $larp->getId());
        $I->assertSame(MiroOAuthProvider::BOARDS_READ_SCOPE, $authorizationQuery['scope']);
        $I->assertSame(self::MIRO_CALLBACK_PATH, parse_url($authorizationQuery['redirect_uri'], PHP_URL_PATH));

        $this->visitMiroCallback($I, ['state' => $authorizationQuery['state'], 'code' => 'miro-code']);

        $I->seeResponseCodeIsRedirection();
        $miroIntegration = $this->findMiroIntegration($I, $larp->getId());
        $I->assertNotNull($miroIntegration);
        $I->assertSame(FakeMiroOAuthGateway::accessTokenFor('miro-code'), $miroIntegration->getAccessToken());
        $I->assertSame(FakeMiroOAuthGateway::refreshTokenFor('miro-code'), $miroIntegration->getRefreshToken());
        $I->assertSame(FakeMiroOAuthGateway::TEAM_ID, $miroIntegration->getExternalAccountId());
        $I->assertSame(ConnectionType::UserOauth, $miroIntegration->getConnectionType());
        $I->assertSame(IntegrationStatus::Active, $miroIntegration->getStatus());
        $I->assertGreaterThan(new \DateTimeImmutable('+50 minutes'), $miroIntegration->getExpiresAt());

        $I->followRedirect();
        $I->see('Miro is now connected.');
        $I->amOnRoute('backoffice_larp_integration_settings', ['larp' => $larp->getId()]);
        $I->see('Manage boards', sprintf('a[href="%s"]', $I->getUrl('backoffice_larp_integration_miro_boards', ['larp' => $larp->getId()->toRfc4122()])));
    }

    public function rejectedAuthorizationCodeDoesNotConnect(FunctionalTester $I): void
    {
        $I->wantTo('see a message when Miro rejects the authorization code');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $I->amLoggedInAs($organizer);
        $state = $this->startMiroConnect($I, $larp->getId())['state'];
        $this->visitMiroCallback($I, ['state' => $state, 'code' => FakeMiroOAuthGateway::REJECTED_CODE]);

        $I->seeResponseCodeIsRedirection();
        $I->followRedirect();
        $I->see('Could not connect Miro. Please try again.');
        $I->assertNull($this->findMiroIntegration($I, $larp->getId()));
    }

    public function disconnectRemovesTheBoardsItemsAndIntegrationOfThisLarp(FunctionalTester $I): void
    {
        $I->wantTo('disconnect Miro and remove the boards and items of this LARP only');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $miroIntegration = $this->createMiroIntegration($larp, $organizer);
        $boardSource = ExternalSourceFactory::new()->with([
            'integration' => $miroIntegration,
            'kind' => ExternalSourceKind::MiroBoard,
            'externalId' => IntegrationFixtureRouters::MIRO_BOARD_ID,
        ])->create();
        ExternalItemFactory::new()->with(['source' => $boardSource])->create();

        $otherOrganizer = UserFactory::createApprovedUser();
        $otherLarp = LarpFactory::createDraftLarp($otherOrganizer);
        $otherIntegration = $this->createMiroIntegration($otherLarp, $otherOrganizer);
        $otherBoardSource = ExternalSourceFactory::new()->with([
            'integration' => $otherIntegration,
            'kind' => ExternalSourceKind::MiroBoard,
            'externalId' => IntegrationFixtureRouters::MIRO_BOARD_ID,
        ])->create();
        ExternalItemFactory::new()->with(['source' => $otherBoardSource])->create();

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integration_settings', ['larp' => $larp->getId()]);
        $I->seeResponseCodeIsSuccessful();
        $I->seeElement(sprintf('button[data-delete-url="%s"]', $this->disconnectUrl($I, $larp)));

        $I->stopFollowingRedirects();
        $I->sendAjaxPostRequest($this->disconnectUrl($I, $larp), ['_token' => $I->grabValueFrom('#deleteModal input[name="_token"]')]);

        $I->seeResponseCodeIsRedirection();
        $I->assertNull($this->findMiroIntegration($I, $larp->getId()));
        $entityManager = $I->getEntityManager();
        $I->assertNull($entityManager->find(ExternalSource::class, $boardSource->getId()));
        $I->assertCount(0, $entityManager->getRepository(ExternalItem::class)->findBy(['source' => $boardSource->getId()]));
        $I->assertNotNull($this->findMiroIntegration($I, $otherLarp->getId()));
        $I->assertNotNull($entityManager->find(ExternalSource::class, $otherBoardSource->getId()));
        $I->assertCount(1, $entityManager->getRepository(ExternalItem::class)->findBy(['source' => $otherBoardSource->getId()]));
    }

    public function disconnectWithoutAValidTokenIsRejected(FunctionalTester $I): void
    {
        $I->wantTo('verify Miro cannot be disconnected without a CSRF token');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $this->createMiroIntegration($larp, $organizer);

        $I->amLoggedInAs($organizer);
        $I->stopFollowingRedirects();
        $I->sendAjaxPostRequest($this->disconnectUrl($I, $larp), ['_token' => 'forged']);

        $I->seeResponseCodeIs(403);
        $I->assertNotNull($this->findMiroIntegration($I, $larp->getId()));
    }

    public function onlyOrganizersCanManageMiro(FunctionalTester $I): void
    {
        $I->wantTo('verify a story writer can neither connect, pick boards nor disconnect Miro');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $this->createMiroIntegration($larp, $organizer);
        $storyWriter = UserFactory::createApprovedUser();
        LarpParticipantFactory::new()->forLarp($larp)->forUser($storyWriter)->storyWriter()->create();

        $I->amLoggedInAs($storyWriter);
        $I->stopFollowingRedirects();

        $I->amOnRoute('backoffice_larp_connect_integration', [
            'larp' => $larp->getId(),
            'provider' => LarpIntegrationProvider::Miro->value,
        ]);
        $I->seeResponseCodeIs(403);

        $I->amOnRoute('backoffice_larp_integration_miro_boards', ['larp' => $larp->getId()]);
        $I->seeResponseCodeIs(403);

        $I->sendAjaxPostRequest($I->getUrl('backoffice_larp_integration_miro_board_toggle', ['larp' => $larp->getId()->toRfc4122()]), [
            'boardId' => IntegrationFixtureRouters::MIRO_BOARD_ID,
            'selected' => '1',
        ]);
        $I->seeResponseCodeIs(403);

        $I->sendAjaxPostRequest($this->disconnectUrl($I, $larp), []);
        $I->seeResponseCodeIs(403);

        $I->assertNotNull($this->findMiroIntegration($I, $larp->getId()));
        $I->assertCount(0, $I->getEntityManager()->getRepository(ExternalSource::class)->findAll());
    }

    public function boardSelectionIsSavedPerLarp(FunctionalTester $I): void
    {
        $I->wantTo('select and deselect boards of the Miro team for a LARP');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $miroIntegration = $this->createMiroIntegration($larp, $organizer);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integration_miro_boards', ['larp' => $larp->getId()]);
        $I->seeResponseCodeIsSuccessful();
        $I->see('Shadows of Ardenne — plot map');
        $I->see('Event timeline');

        $I->submitForm(sprintf('tr[data-miro-board="%s"] form[name="miro_board_toggle"]', IntegrationFixtureRouters::MIRO_BOARD_ID), []);
        $I->seeResponseCodeIsSuccessful();
        $I->see('Board "Shadows of Ardenne — plot map" is now part of this LARP.');
        $I->see('Selected', sprintf('tr[data-miro-board="%s"] .badge', IntegrationFixtureRouters::MIRO_BOARD_ID));
        $I->see('Not selected', sprintf('tr[data-miro-board="%s"] .badge', self::SECOND_BOARD_ID));

        $boardSource = $this->findBoardSource($I, $miroIntegration, IntegrationFixtureRouters::MIRO_BOARD_ID);
        $I->assertNotNull($boardSource);
        $I->assertSame(ExternalSourceKind::MiroBoard, $boardSource->getKind());
        $I->assertSame('Shadows of Ardenne — plot map', $boardSource->getTitle());
        $I->assertSame('https://miro.com/app/board/uXjVOD6LSME=', $boardSource->getUrl());
        $I->assertTrue($boardSource->isEnabled());
        $I->assertNull($this->findBoardSource($I, $miroIntegration, self::SECOND_BOARD_ID));

        $I->submitForm(sprintf('tr[data-miro-board="%s"] form[name="miro_board_toggle"]', IntegrationFixtureRouters::MIRO_BOARD_ID), []);
        $I->seeResponseCodeIsSuccessful();
        $I->see('Not selected', sprintf('tr[data-miro-board="%s"] .badge', IntegrationFixtureRouters::MIRO_BOARD_ID));
        $I->assertFalse($this->findBoardSource($I, $miroIntegration, IntegrationFixtureRouters::MIRO_BOARD_ID)?->isEnabled());
    }

    public function boardOutsideTheConnectedTeamCannotBeSelected(FunctionalTester $I): void
    {
        $I->wantTo('verify a board id that Miro does not list for the team is not stored');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $miroIntegration = $this->createMiroIntegration($larp, $organizer);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integration_miro_boards', ['larp' => $larp->getId()]);
        $boardToken = $I->grabValueFrom('form[name="miro_board_toggle"] input[name="_token"]');
        $I->stopFollowingRedirects();
        $I->sendAjaxPostRequest($I->getUrl('backoffice_larp_integration_miro_board_toggle', ['larp' => $larp->getId()->toRfc4122()]), [
            '_token' => $boardToken,
            'boardId' => 'uXjForeignBoard=',
            'selected' => '1',
        ]);

        $I->seeResponseCodeIs(404);
        $I->assertNull($this->findBoardSource($I, $miroIntegration, 'uXjForeignBoard='));
    }

    public function revokedRefreshTokenShowsTheReconnectState(FunctionalTester $I): void
    {
        $I->wantTo('see the reconnect state when Miro rejects the refresh token');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $miroIntegration = LarpIntegrationFactory::new()
            ->forLarp($larp)
            ->createdBy($organizer)
            ->miro()
            ->expired()
            ->with([
                'accessToken' => 'expired-miro-access-token',
                'refreshToken' => MiroTokenEndpoint::REVOKED_REFRESH_TOKEN_PREFIX . 'miro-refresh-token',
                'externalAccountId' => FakeMiroOAuthGateway::TEAM_ID,
            ])
            ->create();

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integration_miro_boards', ['larp' => $larp->getId()]);

        $I->seeResponseCodeIsSuccessful();
        $I->seeElement('[data-test="miro-reconnect"]');
        $I->see('Reconnect Miro');
        $I->dontSee('Shadows of Ardenne — plot map');

        $storedIntegration = $this->findMiroIntegration($I, $larp->getId());
        $I->assertSame(IntegrationStatus::NeedsReauth, $storedIntegration?->getStatus());
        $I->assertSame('Refresh token is invalid or revoked', $storedIntegration?->getLastError());

        $I->amOnRoute('backoffice_larp_integration_settings', ['larp' => $larp->getId()]);
        $I->seeElement('[data-test="miro-reconnect"]');
        $I->see('Reconnect Miro', sprintf('a[href="%s"]', $I->getUrl('backoffice_larp_connect_integration', [
            'larp' => $larp->getId()->toRfc4122(),
            'provider' => LarpIntegrationProvider::Miro->value,
        ])));
        $I->assertEquals($miroIntegration->getId(), $storedIntegration?->getId());
    }

    public function reconnectingRestoresTheIntegration(FunctionalTester $I): void
    {
        $I->wantTo('reconnect Miro after the refresh token was revoked and keep the selected boards');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $miroIntegration = LarpIntegrationFactory::new()
            ->forLarp($larp)
            ->createdBy($organizer)
            ->miro()
            ->expired()
            ->needsReauth()
            ->with(['externalAccountId' => FakeMiroOAuthGateway::TEAM_ID])
            ->create();
        $boardSource = ExternalSourceFactory::new()->with([
            'integration' => $miroIntegration,
            'kind' => ExternalSourceKind::MiroBoard,
            'externalId' => IntegrationFixtureRouters::MIRO_BOARD_ID,
        ])->create();

        $I->amLoggedInAs($organizer);
        $state = $this->startMiroConnect($I, $larp->getId())['state'];
        $this->visitMiroCallback($I, ['state' => $state, 'code' => 'reconnect-code']);

        $I->seeResponseCodeIsRedirection();
        $I->getEntityManager()->clear();
        $miroIntegrations = $I->getEntityManager()->getRepository(LarpIntegration::class)->findBy([
            'larp' => $larp->getId(),
            'provider' => LarpIntegrationProvider::Miro,
        ]);
        $I->assertCount(1, $miroIntegrations);
        $I->assertSame(IntegrationStatus::Active, $miroIntegrations[0]->getStatus());
        $I->assertSame(FakeMiroOAuthGateway::refreshTokenFor('reconnect-code'), $miroIntegrations[0]->getRefreshToken());
        $I->assertNotNull($I->getEntityManager()->find(ExternalSource::class, $boardSource->getId()));
    }

    private function createMiroIntegration(Larp $larp, mixed $organizer): LarpIntegration
    {
        return LarpIntegrationFactory::new()
            ->forLarp($larp)
            ->createdBy($organizer)
            ->miro()
            ->with([
                'accessToken' => 'valid-miro-access-token',
                'externalAccountId' => FakeMiroOAuthGateway::TEAM_ID,
                'scopes' => MiroOAuthProvider::BOARDS_READ_SCOPE,
            ])
            ->create()
            ->_real();
    }

    /**
     * @return array<string, string>
     */
    private function startMiroConnect(FunctionalTester $I, Uuid $larpId): array
    {
        $I->stopFollowingRedirects();
        $I->amOnRoute('backoffice_larp_connect_integration', [
            'larp' => $larpId->toRfc4122(),
            'provider' => LarpIntegrationProvider::Miro->value,
        ]);
        $I->seeResponseCodeIsRedirection();

        $authorizationUrl = (string) $I->grabResponse()->headers->get('Location');
        $I->assertStringStartsWith(FakeMiroOAuthGateway::AUTHORIZATION_ENDPOINT . '?', $authorizationUrl);
        parse_str((string) parse_url($authorizationUrl, PHP_URL_QUERY), $authorizationQuery);

        return $authorizationQuery;
    }

    /**
     * @param array<string, string> $query
     */
    private function visitMiroCallback(FunctionalTester $I, array $query): void
    {
        $I->stopFollowingRedirects();
        $I->amOnPage(self::MIRO_CALLBACK_PATH . '?' . http_build_query($query));
    }

    private function disconnectUrl(FunctionalTester $I, Larp $larp): string
    {
        return $I->getUrl('backoffice_larp_integration_miro_disconnect', ['larp' => $larp->getId()->toRfc4122()]);
    }

    private function findMiroIntegration(FunctionalTester $I, Uuid $larpId): ?LarpIntegration
    {
        $entityManager = $I->getEntityManager();
        $entityManager->clear();

        return $entityManager->getRepository(LarpIntegration::class)->findOneBy([
            'larp' => $larpId,
            'provider' => LarpIntegrationProvider::Miro,
        ]);
    }

    private function findBoardSource(FunctionalTester $I, LarpIntegration $integration, string $boardId): ?ExternalSource
    {
        $entityManager = $I->getEntityManager();
        $entityManager->clear();

        return $entityManager->getRepository(ExternalSource::class)->findOneBy([
            'integration' => $integration->getId(),
            'externalId' => $boardId,
        ]);
    }
}
