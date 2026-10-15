<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Miro\Snapshot;

use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Miro\Client\MiroApiClientFactory;
use App\Domain\Integrations\Miro\Client\MiroApiException;
use App\Domain\Integrations\Miro\Client\MiroAuthorizationException;
use App\Domain\Integrations\Miro\Client\MiroRateLimitExceededException;
use App\Domain\Integrations\Service\Credential\OAuthCredentialProvider;
use App\Domain\Integrations\Service\Exceptions\ReAuthenticationNeededException;
use App\Domain\Integrations\Service\Exceptions\SourceSyncForbiddenException;
use App\Domain\Integrations\Service\Exceptions\SourceSyncRateLimitedException;
use App\Domain\Integrations\Service\Exceptions\SourceSyncUnauthorizedException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;

final readonly class MiroBoardSnapshotProvider
{
    public const CACHE_POOL = 'cache.miro_board_snapshots';

    public function __construct(
        #[Autowire(service: self::CACHE_POOL)]
        private CacheInterface $snapshotCache,
        private MiroApiClientFactory $miroApiClientFactory,
        private OAuthCredentialProvider $oauthCredentialProvider,
        private MiroBoardSnapshotBuilder $snapshotBuilder,
    ) {
    }

    /**
     * @throws SourceSyncRateLimitedException
     * @throws SourceSyncUnauthorizedException
     * @throws SourceSyncForbiddenException
     * @throws ReAuthenticationNeededException
     * @throws MiroApiException
     */
    public function snapshot(LarpIntegration $integration, string $boardId): MiroBoardSnapshot
    {
        return $this->snapshotCache->get(
            $this->cacheKey($integration, $boardId),
            fn (): MiroBoardSnapshot => $this->build($integration, $boardId),
        );
    }

    public function forget(LarpIntegration $integration, string $boardId): void
    {
        $this->snapshotCache->delete($this->cacheKey($integration, $boardId));
    }

    private function build(LarpIntegration $integration, string $boardId): MiroBoardSnapshot
    {
        $miroApiClient = $this->miroApiClientFactory->createWithoutRateLimitRetries(
            $this->oauthCredentialProvider->accessToken($integration),
        );

        try {
            return $this->snapshotBuilder->build($miroApiClient, $boardId);
        } catch (MiroRateLimitExceededException $rateLimited) {
            throw new SourceSyncRateLimitedException($rateLimited->getRetryAfterSeconds(), $rateLimited);
        } catch (MiroAuthorizationException $unauthorized) {
            throw new SourceSyncUnauthorizedException($unauthorized->getMessage(), $unauthorized);
        } catch (MiroApiException $apiFailure) {
            if (in_array($apiFailure->getHttpStatusCode(), [403, 404], true)) {
                throw new SourceSyncForbiddenException($apiFailure->getMessage(), $apiFailure);
            }

            throw $apiFailure;
        }
    }

    private function cacheKey(LarpIntegration $integration, string $boardId): string
    {
        return 'miro_board_' . hash('xxh128', $integration->getId()->toRfc4122() . '|' . $boardId);
    }
}
