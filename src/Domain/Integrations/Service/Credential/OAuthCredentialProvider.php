<?php

namespace App\Domain\Integrations\Service\Credential;

use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Repository\LarpIntegrationRepository;
use App\Domain\Integrations\Service\Capability\AccessTokenProviderInterface;
use App\Domain\Integrations\Service\Exceptions\InvalidGrantException;
use App\Domain\Integrations\Service\Exceptions\ReAuthenticationNeededException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\Lock\LockFactory;
use Symfony\Contracts\Service\ServiceProviderInterface;

readonly class OAuthCredentialProvider implements AccessTokenProviderInterface
{
    private const EXPIRY_LEEWAY_SECONDS = 30;
    private const LOCK_TTL_SECONDS = 60.0;

    /**
     * @param ServiceProviderInterface<OAuthTokenRefresherInterface> $tokenRefreshers
     */
    public function __construct(
        #[AutowireLocator(OAuthTokenRefresherInterface::class, defaultIndexMethod: 'providerKey')]
        private ServiceProviderInterface $tokenRefreshers,
        private LockFactory $lockFactory,
        private LarpIntegrationRepository $larpIntegrationRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public static function lockKey(LarpIntegration $integration): string
    {
        return 'larp_integration_token_refresh_' . $integration->getId()->toRfc4122();
    }

    public function authorizationHeader(LarpIntegration $integration): string
    {
        return 'Bearer ' . $this->accessToken($integration);
    }

    public function accessToken(LarpIntegration $integration): string
    {
        $accessToken = $integration->getAccessToken();
        if ($accessToken !== null && !$this->isExpired($accessToken, $integration->getExpiresAt())) {
            return $accessToken;
        }

        $lock = $this->lockFactory->createLock(self::lockKey($integration), self::LOCK_TTL_SECONDS);
        $lock->acquire(true);

        try {
            return $this->refreshUnderLock($integration);
        } finally {
            $lock->release();
        }
    }

    /**
     * @throws ReAuthenticationNeededException
     */
    private function refreshUnderLock(LarpIntegration $integration): string
    {
        $storedToken = $this->larpIntegrationRepository->findStoredToken($integration->getId());
        $storedAccessToken = $storedToken['accessToken'] ?? null;
        if ($storedToken !== null && $storedAccessToken !== null && !$this->isExpired($storedAccessToken, $storedToken['expiresAt'])) {
            $integration->setAccessToken($storedAccessToken);
            $integration->setRefreshToken($storedToken['refreshToken']);
            $integration->setExpiresAt($storedToken['expiresAt']);

            return $storedAccessToken;
        }

        $refreshToken = $storedToken['refreshToken'] ?? $integration->getRefreshToken();
        if ($refreshToken === null || $refreshToken === '') {
            $this->requireReauthentication($integration, 'Missing refresh token.');
        }

        try {
            $refreshedToken = $this->tokenRefresher($integration)->refresh($refreshToken);
        } catch (InvalidGrantException $exception) {
            $this->requireReauthentication($integration, $exception->getMessage());
        }

        $integration->setAccessToken($refreshedToken->accessToken);
        $integration->setRefreshToken($refreshedToken->refreshToken ?? $refreshToken);
        $integration->setExpiresAt($refreshedToken->expiresAt);
        $integration->markActive();
        $this->entityManager->flush();

        return $refreshedToken->accessToken;
    }

    private function tokenRefresher(LarpIntegration $integration): OAuthTokenRefresherInterface
    {
        $providerKey = $integration->getProvider()->value;
        if (!$this->tokenRefreshers->has($providerKey)) {
            throw new \LogicException(sprintf('No OAuth token refresher is registered for "%s".', $providerKey));
        }

        return $this->tokenRefreshers->get($providerKey);
    }

    private function isExpired(string $accessToken, ?\DateTimeInterface $expiresAt): bool
    {
        if ($accessToken === '') {
            return true;
        }

        if (!$expiresAt instanceof \DateTimeInterface) {
            return false;
        }

        return $expiresAt->getTimestamp() <= time() + self::EXPIRY_LEEWAY_SECONDS;
    }

    /**
     * @throws ReAuthenticationNeededException
     */
    private function requireReauthentication(LarpIntegration $integration, string $reason): never
    {
        $integration->markNeedsReauth($reason);
        $this->entityManager->flush();

        throw new ReAuthenticationNeededException($integration->getId()->toRfc4122());
    }
}
