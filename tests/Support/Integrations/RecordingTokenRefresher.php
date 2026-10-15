<?php

declare(strict_types=1);

namespace Tests\Support\Integrations;

use App\Domain\Integrations\DTO\RefreshedToken;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Service\Credential\OAuthTokenRefresherInterface;
use App\Domain\Integrations\Service\Exceptions\InvalidGrantException;
use Symfony\Component\Lock\LockFactory;

final class RecordingTokenRefresher implements OAuthTokenRefresherInterface
{
    /**
     * @var list<string>
     */
    private array $refreshTokensUsed = [];

    /**
     * @var list<bool>
     */
    private array $lockHeldDuringRefresh = [];

    public function __construct(
        private readonly LockFactory $lockFactory,
        private readonly string $lockKey,
        private readonly bool $rejectRefreshToken = false,
    ) {
    }

    public static function providerKey(): string
    {
        return LarpIntegrationProvider::Google->value;
    }

    public function refresh(string $refreshToken): RefreshedToken
    {
        $this->refreshTokensUsed[] = $refreshToken;
        $this->lockHeldDuringRefresh[] = $this->isLockHeldElsewhere();

        if ($this->rejectRefreshToken) {
            throw new InvalidGrantException('Token has been expired or revoked.');
        }

        $requestNumber = count($this->refreshTokensUsed);

        return RefreshedToken::fromExpiresIn(
            'refreshed-access-token-' . $requestNumber,
            'rotated-refresh-token-' . $requestNumber,
            3600,
        );
    }

    /**
     * @return list<string>
     */
    public function refreshTokensUsed(): array
    {
        return $this->refreshTokensUsed;
    }

    /**
     * @return list<bool>
     */
    public function lockHeldDuringRefresh(): array
    {
        return $this->lockHeldDuringRefresh;
    }

    private function isLockHeldElsewhere(): bool
    {
        $competingLock = $this->lockFactory->createLock($this->lockKey);
        if (!$competingLock->acquire(false)) {
            return true;
        }

        $competingLock->release();

        return false;
    }
}
