<?php

declare(strict_types=1);

namespace Tests\Functional\Integrations\Miro;

use App\Domain\Integrations\Entity\Enum\IntegrationStatus;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Miro\Credential\MiroTokenRefresher;
use App\Domain\Integrations\Repository\LarpIntegrationRepository;
use App\Domain\Integrations\Service\Credential\OAuthCredentialProvider;
use App\Domain\Integrations\Service\Exceptions\ReAuthenticationNeededException;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\Lock\LockFactory;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\Factory\Integrations\LarpIntegrationFactory;
use Tests\Support\FunctionalTester;
use Tests\Support\Integrations\FakeMiroOAuthGateway;
use Tests\Support\Integrations\MiroTokenEndpoint;

class MiroTokenRefreshCest
{
    public function twoConcurrentRefreshesMakeOneTokenCallAndSaveTheRotatedRefreshToken(FunctionalTester $I): void
    {
        $I->wantTo('verify two requests holding the same expired Miro token make one token call and save the rotated refresh token');

        $integration = $this->createExpiredMiroIntegration('miro-refresh-token-before-rotation');
        $firstRequestIntegration = $integration;
        $secondRequestIntegration = clone $integration;
        $miroTokenEndpoint = new MiroTokenEndpoint();
        $lockHeldDuringTokenCall = [];
        $lockFactory = $this->lockFactory($I);
        $lockKey = OAuthCredentialProvider::lockKey($integration);
        $httpClient = new MockHttpClient(static function (string $method, string $url, array $options) use ($miroTokenEndpoint, $lockFactory, $lockKey, &$lockHeldDuringTokenCall) {
            $competingLock = $lockFactory->createLock($lockKey);
            $lockHeldDuringTokenCall[] = !$competingLock->acquire(false);

            return $miroTokenEndpoint($method, $url, $options);
        });
        $oauthCredentialProvider = $this->createOAuthCredentialProvider($I, new MiroTokenRefresher($httpClient, 'test-miro-client-id', 'test-miro-secret'));

        $firstAccessToken = $oauthCredentialProvider->accessToken($firstRequestIntegration);
        $secondAccessToken = $oauthCredentialProvider->accessToken($secondRequestIntegration);

        $I->assertCount(1, $miroTokenEndpoint->tokenRequests());
        $I->assertSame('refresh_token', $miroTokenEndpoint->tokenRequests()[0]['grant_type']);
        $I->assertSame('miro-refresh-token-before-rotation', $miroTokenEndpoint->tokenRequests()[0]['refresh_token']);
        $I->assertSame([true], $lockHeldDuringTokenCall);
        $I->assertSame(MiroTokenEndpoint::accessToken(1), $firstAccessToken);
        $I->assertSame(MiroTokenEndpoint::accessToken(1), $secondAccessToken);
        $I->assertSame(MiroTokenEndpoint::rotatedRefreshToken(1), $secondRequestIntegration->getRefreshToken());

        $storedIntegration = $this->reloadIntegration($I, $integration);
        $I->assertSame(MiroTokenEndpoint::accessToken(1), $storedIntegration->getAccessToken());
        $I->assertSame(MiroTokenEndpoint::rotatedRefreshToken(1), $storedIntegration->getRefreshToken());
        $I->assertGreaterThan(new \DateTimeImmutable('+50 minutes'), $storedIntegration->getExpiresAt());
        $I->assertSame(IntegrationStatus::Active, $storedIntegration->getStatus());
    }

    public function revokedRefreshTokenMarksTheMiroIntegrationAsNeedingReauthentication(FunctionalTester $I): void
    {
        $I->wantTo('verify a revoked Miro refresh token puts the connection into the needs_reauth state');

        $integration = $this->createExpiredMiroIntegration(MiroTokenEndpoint::REVOKED_REFRESH_TOKEN_PREFIX . 'miro-refresh-token');
        $miroTokenEndpoint = new MiroTokenEndpoint();
        $oauthCredentialProvider = $this->createOAuthCredentialProvider(
            $I,
            new MiroTokenRefresher(new MockHttpClient($miroTokenEndpoint), 'test-miro-client-id', 'test-miro-secret'),
        );

        $reauthenticationRequired = false;
        try {
            $oauthCredentialProvider->accessToken($integration);
        } catch (ReAuthenticationNeededException) {
            $reauthenticationRequired = true;
        }

        $I->assertTrue($reauthenticationRequired);
        $I->assertCount(1, $miroTokenEndpoint->tokenRequests());
        $storedIntegration = $this->reloadIntegration($I, $integration);
        $I->assertSame(IntegrationStatus::NeedsReauth, $storedIntegration->getStatus());
        $I->assertSame('Refresh token is invalid or revoked', $storedIntegration->getLastError());
    }

    public function containerRefreshesMiroTokensThroughTheSharedCredentialProvider(FunctionalTester $I): void
    {
        $I->wantTo('verify the Miro refresher is registered with the shared OAuth credential provider');

        $integration = $this->createExpiredMiroIntegration('miro-refresh-token-before-rotation');

        /** @var OAuthCredentialProvider $oauthCredentialProvider */
        $oauthCredentialProvider = $I->grabService(OAuthCredentialProvider::class);
        $accessToken = $oauthCredentialProvider->accessToken($integration);

        $I->assertSame(MiroTokenEndpoint::accessToken(1), $accessToken);
        $I->assertSame(MiroTokenEndpoint::rotatedRefreshToken(1), $this->reloadIntegration($I, $integration)->getRefreshToken());
    }

    private function createExpiredMiroIntegration(string $refreshToken): LarpIntegration
    {
        $organizer = UserFactory::createApprovedUser();

        return LarpIntegrationFactory::new()
            ->forLarp(LarpFactory::createDraftLarp($organizer))
            ->createdBy($organizer)
            ->miro()
            ->expired()
            ->with([
                'accessToken' => 'expired-miro-access-token',
                'refreshToken' => $refreshToken,
                'externalAccountId' => FakeMiroOAuthGateway::TEAM_ID,
            ])
            ->create()
            ->_real();
    }

    private function createOAuthCredentialProvider(FunctionalTester $I, MiroTokenRefresher $miroTokenRefresher): OAuthCredentialProvider
    {
        $entityManager = $I->getEntityManager();
        /** @var LarpIntegrationRepository $larpIntegrationRepository */
        $larpIntegrationRepository = $entityManager->getRepository(LarpIntegration::class);

        return new OAuthCredentialProvider(
            new ServiceLocator([LarpIntegrationProvider::Miro->value => static fn (): MiroTokenRefresher => $miroTokenRefresher]),
            $this->lockFactory($I),
            $larpIntegrationRepository,
            $entityManager,
        );
    }

    private function lockFactory(FunctionalTester $I): LockFactory
    {
        /** @var LockFactory $lockFactory */
        $lockFactory = $I->grabService(LockFactory::class);

        return $lockFactory;
    }

    private function reloadIntegration(FunctionalTester $I, LarpIntegration $integration): LarpIntegration
    {
        $entityManager = $I->getEntityManager();
        $entityManager->clear();
        $storedIntegration = $entityManager->find(LarpIntegration::class, $integration->getId());
        $I->assertNotNull($storedIntegration);

        return $storedIntegration;
    }
}
