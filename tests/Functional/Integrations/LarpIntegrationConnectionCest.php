<?php

declare(strict_types=1);

namespace Tests\Functional\Integrations;

use App\Domain\Integrations\Entity\Enum\ConnectionType;
use App\Domain\Integrations\Entity\Enum\IntegrationStatus;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Service\Google\GoogleIntegrationService;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Token\AccessToken;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\Factory\Integrations\LarpIntegrationFactory;
use Tests\Support\FunctionalTester;

class LarpIntegrationConnectionCest
{
    public function reconnectingGoogleUpdatesTheExistingRow(FunctionalTester $I): void
    {
        $I->wantTo('reconnect Google Drive and keep exactly one integration row');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $larpId = $larp->getId()->toRfc4122();
        $googleUser = new GoogleUser(['email' => 'organizer@example.com']);
        $I->amLoggedInAs($organizer);

        /** @var GoogleIntegrationService $googleIntegrationService */
        $googleIntegrationService = $I->grabService(GoogleIntegrationService::class);
        $firstIntegration = $googleIntegrationService->createGoogleDriveIntegration(
            new AccessToken(['access_token' => 'first-access-token', 'refresh_token' => 'first-refresh-token', 'expires_in' => 3600]),
            $googleUser,
            $larpId,
        );
        $firstIntegration->markNeedsReauth('Token has been expired or revoked.');
        $I->getEntityManager()->flush();

        $reconnectedIntegration = $googleIntegrationService->createGoogleDriveIntegration(
            new AccessToken(['access_token' => 'second-access-token', 'expires_in' => 3600]),
            $googleUser,
            $larpId,
        );

        $I->assertEquals($firstIntegration->getId(), $reconnectedIntegration->getId());
        $I->getEntityManager()->clear();
        $googleIntegrations = $I->getEntityManager()->getRepository(LarpIntegration::class)->findBy([
            'larp' => $larpId,
            'provider' => LarpIntegrationProvider::Google,
        ]);
        $I->assertCount(1, $googleIntegrations);
        $storedIntegration = $googleIntegrations[0];
        $I->assertSame('second-access-token', $storedIntegration->getAccessToken());
        $I->assertSame('first-refresh-token', $storedIntegration->getRefreshToken());
        $I->assertSame('organizer@example.com', $storedIntegration->getExternalAccountId());
        $I->assertSame(ConnectionType::UserOauth, $storedIntegration->getConnectionType());
        $I->assertSame(IntegrationStatus::Active, $storedIntegration->getStatus());
        $I->assertNull($storedIntegration->getLastError());
    }

    public function botInstallPersistsWithoutTokens(FunctionalTester $I): void
    {
        $I->wantTo('persist a bot install integration that has no user tokens');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $botIntegration = LarpIntegrationFactory::new()
            ->forLarp($larp)
            ->createdBy($organizer)
            ->forProvider(LarpIntegrationProvider::Facebook)
            ->botInstall('guild-123456')
            ->with(['config' => ['channels' => ['lore', 'plot']]])
            ->create();
        $botIntegrationId = $botIntegration->getId();

        $I->getEntityManager()->clear();
        $storedIntegration = $I->getEntityManager()->find(LarpIntegration::class, $botIntegrationId);

        $I->assertNotNull($storedIntegration);
        $I->assertSame(ConnectionType::BotInstall, $storedIntegration->getConnectionType());
        $I->assertSame('guild-123456', $storedIntegration->getExternalAccountId());
        $I->assertNull($storedIntegration->getAccessToken());
        $I->assertNull($storedIntegration->getRefreshToken());
        $I->assertNull($storedIntegration->getExpiresAt());
        $I->assertSame(['channels' => ['lore', 'plot']], $storedIntegration->getConfig());
    }
}
