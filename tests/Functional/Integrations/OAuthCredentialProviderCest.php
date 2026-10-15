<?php

declare(strict_types=1);

namespace Tests\Functional\Integrations;

use App\Domain\Integrations\Entity\Enum\IntegrationStatus;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Repository\LarpIntegrationRepository;
use App\Domain\Integrations\Service\Credential\OAuthCredentialProvider;
use App\Domain\Integrations\Service\Exceptions\ReAuthenticationNeededException;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Lock\LockFactory;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\Factory\Integrations\LarpIntegrationFactory;
use Tests\Support\FunctionalTester;
use Tests\Support\Integrations\RecordingTokenRefresher;

class OAuthCredentialProviderCest
{
    public function twoConcurrentRefreshesProduceOneTokenRequest(FunctionalTester $I): void
    {
        $I->wantTo('verify two requests holding the same expired token refresh it only once');

        $integration = $this->createExpiredGoogleIntegration('original-refresh-token');
        $firstRequestIntegration = $integration;
        $secondRequestIntegration = clone $integration;
        $tokenRefresher = $this->createTokenRefresher($I, $integration);
        $oauthCredentialProvider = $this->createOAuthCredentialProvider($I, $tokenRefresher);

        $firstAuthorizationHeader = $oauthCredentialProvider->authorizationHeader($firstRequestIntegration);
        $secondAuthorizationHeader = $oauthCredentialProvider->authorizationHeader($secondRequestIntegration);

        $I->assertSame(['original-refresh-token'], $tokenRefresher->refreshTokensUsed());
        $I->assertSame([true], $tokenRefresher->lockHeldDuringRefresh());
        $I->assertSame('Bearer refreshed-access-token-1', $firstAuthorizationHeader);
        $I->assertSame('Bearer refreshed-access-token-1', $secondAuthorizationHeader);
        $I->assertSame('rotated-refresh-token-1', $secondRequestIntegration->getRefreshToken());

        $storedIntegration = $this->reloadIntegration($I, $integration);
        $I->assertSame('refreshed-access-token-1', $storedIntegration->getAccessToken());
        $I->assertSame('rotated-refresh-token-1', $storedIntegration->getRefreshToken());
        $I->assertGreaterThan(new \DateTimeImmutable('+50 minutes'), $storedIntegration->getExpiresAt());
        $I->assertSame(IntegrationStatus::Active, $storedIntegration->getStatus());
    }

    public function tokenRefreshedByAnotherWorkerIsReused(FunctionalTester $I): void
    {
        $I->wantTo('reuse a token another worker refreshed while this request waited for the lock');

        $integration = $this->createExpiredGoogleIntegration('original-refresh-token');
        $staleIntegration = clone $integration;
        $integration->setAccessToken('token-refreshed-by-worker');
        $integration->setRefreshToken('refresh-token-rotated-by-worker');
        $integration->setExpiresAt(new \DateTimeImmutable('+1 hour'));
        $I->getEntityManager()->flush();
        $tokenRefresher = $this->createTokenRefresher($I, $integration);

        $accessToken = $this->createOAuthCredentialProvider($I, $tokenRefresher)->accessToken($staleIntegration);

        $I->assertSame('token-refreshed-by-worker', $accessToken);
        $I->assertSame([], $tokenRefresher->refreshTokensUsed());
        $I->assertSame('refresh-token-rotated-by-worker', $staleIntegration->getRefreshToken());
    }

    public function validTokenIsReturnedWithoutRefreshing(FunctionalTester $I): void
    {
        $I->wantTo('use a valid access token as is');

        $organizer = UserFactory::createApprovedUser();
        $integration = LarpIntegrationFactory::new()
            ->forLarp(LarpFactory::createDraftLarp($organizer))
            ->createdBy($organizer)
            ->google()
            ->with(['accessToken' => 'still-valid-access-token'])
            ->create()
            ->_real();
        $tokenRefresher = $this->createTokenRefresher($I, $integration);

        $authorizationHeader = $this->createOAuthCredentialProvider($I, $tokenRefresher)->authorizationHeader($integration);

        $I->assertSame('Bearer still-valid-access-token', $authorizationHeader);
        $I->assertSame([], $tokenRefresher->refreshTokensUsed());
    }

    public function invalidGrantMarksTheIntegrationAsNeedingReauthentication(FunctionalTester $I): void
    {
        $I->wantTo('mark the integration as needing re-authentication when the refresh token is rejected');

        $integration = $this->createExpiredGoogleIntegration('revoked-refresh-token');
        $tokenRefresher = $this->createTokenRefresher($I, $integration, rejectRefreshToken: true);
        $oauthCredentialProvider = $this->createOAuthCredentialProvider($I, $tokenRefresher);

        $reauthenticationRequired = false;
        try {
            $oauthCredentialProvider->accessToken($integration);
        } catch (ReAuthenticationNeededException) {
            $reauthenticationRequired = true;
        }

        $I->assertTrue($reauthenticationRequired);
        $storedIntegration = $this->reloadIntegration($I, $integration);
        $I->assertSame(IntegrationStatus::NeedsReauth, $storedIntegration->getStatus());
        $I->assertSame('Token has been expired or revoked.', $storedIntegration->getLastError());
        $I->assertSame('revoked-refresh-token', $storedIntegration->getRefreshToken());
    }

    public function missingRefreshTokenRequiresReauthenticationWithoutATokenRequest(FunctionalTester $I): void
    {
        $I->wantTo('require re-authentication when an expired integration has no refresh token');

        $organizer = UserFactory::createApprovedUser();
        $integration = LarpIntegrationFactory::new()
            ->forLarp(LarpFactory::createDraftLarp($organizer))
            ->createdBy($organizer)
            ->google()
            ->expired()
            ->withoutRefreshToken()
            ->create()
            ->_real();
        $tokenRefresher = $this->createTokenRefresher($I, $integration);

        $reauthenticationRequired = false;
        try {
            $this->createOAuthCredentialProvider($I, $tokenRefresher)->accessToken($integration);
        } catch (ReAuthenticationNeededException) {
            $reauthenticationRequired = true;
        }

        $I->assertTrue($reauthenticationRequired);
        $I->assertSame([], $tokenRefresher->refreshTokensUsed());
        $I->assertSame(IntegrationStatus::NeedsReauth, $this->reloadIntegration($I, $integration)->getStatus());
    }

    private function createExpiredGoogleIntegration(string $refreshToken): LarpIntegration
    {
        $organizer = UserFactory::createApprovedUser();

        return LarpIntegrationFactory::new()
            ->forLarp(LarpFactory::createDraftLarp($organizer))
            ->createdBy($organizer)
            ->google()
            ->expired()
            ->with(['accessToken' => 'expired-access-token', 'refreshToken' => $refreshToken])
            ->create()
            ->_real();
    }

    private function createTokenRefresher(FunctionalTester $I, LarpIntegration $integration, bool $rejectRefreshToken = false): RecordingTokenRefresher
    {
        return new RecordingTokenRefresher($this->lockFactory($I), OAuthCredentialProvider::lockKey($integration), $rejectRefreshToken);
    }

    private function createOAuthCredentialProvider(FunctionalTester $I, RecordingTokenRefresher $tokenRefresher): OAuthCredentialProvider
    {
        $entityManager = $I->getEntityManager();
        /** @var LarpIntegrationRepository $larpIntegrationRepository */
        $larpIntegrationRepository = $entityManager->getRepository(LarpIntegration::class);

        return new OAuthCredentialProvider(
            new ServiceLocator([LarpIntegrationProvider::Google->value => static fn (): RecordingTokenRefresher => $tokenRefresher]),
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
