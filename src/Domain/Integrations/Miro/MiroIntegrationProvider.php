<?php

namespace App\Domain\Integrations\Miro;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Larp;
use App\Domain\Integrations\DTO\ConnectionCallback;
use App\Domain\Integrations\DTO\IntegrationHealth;
use App\Domain\Integrations\DTO\RemoteSource;
use App\Domain\Integrations\Entity\Enum\ConnectionType;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Miro\Client\MiroApiClientFactory;
use App\Domain\Integrations\Miro\Client\MiroApiException;
use App\Domain\Integrations\Miro\Client\MiroAuthorizationException;
use App\Domain\Integrations\Miro\OAuth\MiroOAuthGatewayInterface;
use App\Domain\Integrations\Repository\ExternalSourceRepository;
use App\Domain\Integrations\Repository\LarpIntegrationRepository;
use App\Domain\Integrations\Service\Capability\AccessTokenProviderInterface;
use App\Domain\Integrations\Service\Capability\ConnectableIntegrationInterface;
use App\Domain\Integrations\Service\Credential\OAuthCredentialProvider;
use App\Domain\Integrations\Service\Exceptions\IntegrationConnectionFailedException;
use App\Domain\Integrations\Service\Exceptions\ReAuthenticationNeededException;
use Doctrine\ORM\EntityManagerInterface;

readonly class MiroIntegrationProvider implements ConnectableIntegrationInterface, AccessTokenProviderInterface
{
    public function __construct(
        private OAuthCredentialProvider $oauthCredentialProvider,
        private MiroOAuthGatewayInterface $miroOAuthGateway,
        private MiroApiClientFactory $miroApiClientFactory,
        private LarpIntegrationRepository $larpIntegrationRepository,
        private ExternalSourceRepository $externalSourceRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public static function providerKey(): string
    {
        return LarpIntegrationProvider::Miro->value;
    }

    public function authorizationUrl(Larp $larp, string $state, string $redirectUri): string
    {
        return $this->miroOAuthGateway->authorizationUrl($state, $redirectUri);
    }

    public function completeConnection(Larp $larp, ConnectionCallback $callback, User $connectingUser): LarpIntegration
    {
        $authorizationCode = $callback->authorizationCode();
        if ($authorizationCode === null) {
            throw new IntegrationConnectionFailedException('Miro did not return an authorization code.');
        }

        $authorization = $this->miroOAuthGateway->exchangeAuthorizationCode($authorizationCode, $callback->redirectUri);
        if ($authorization->teamId === null) {
            throw new IntegrationConnectionFailedException('Miro did not return the team of the authorization.');
        }

        $integration = $this->findIntegration($larp);
        if (!$integration instanceof LarpIntegration) {
            $integration = new LarpIntegration();
            $integration->setCreatedBy($connectingUser);
            $integration->setLarp($larp);
            $integration->setProvider(LarpIntegrationProvider::Miro);
        }

        $integration->setConnectionType(ConnectionType::UserOauth);
        $integration->setAccessToken($authorization->accessToken);
        $integration->setRefreshToken($authorization->refreshToken ?? $integration->getRefreshToken());
        $integration->setExpiresAt($authorization->expiresAt);
        $integration->setScopes($authorization->scopes);
        $integration->setExternalAccountId($authorization->teamId);
        $integration->setOwner($authorization->userId);
        $integration->markActive();

        $this->larpIntegrationRepository->save($integration);

        return $integration;
    }

    public function disconnect(LarpIntegration $integration): void
    {
        foreach ($this->externalSourceRepository->findBy(['integration' => $integration]) as $externalSource) {
            $this->entityManager->remove($externalSource);
        }

        $this->entityManager->remove($integration);
        $this->entityManager->flush();
    }

    public function health(LarpIntegration $integration): IntegrationHealth
    {
        try {
            $this->accessToken($integration);
        } catch (ReAuthenticationNeededException) {
            return IntegrationHealth::needsReauth($integration->getLastError());
        }

        return IntegrationHealth::healthy();
    }

    public function authorizationHeader(LarpIntegration $integration): string
    {
        return $this->oauthCredentialProvider->authorizationHeader($integration);
    }

    public function accessToken(LarpIntegration $integration): string
    {
        return $this->oauthCredentialProvider->accessToken($integration);
    }

    /**
     * @return list<RemoteSource>
     *
     * @throws ReAuthenticationNeededException
     * @throws MiroApiException
     */
    public function listBoards(LarpIntegration $integration): array
    {
        $miroApiClient = $this->miroApiClientFactory->create($this->accessToken($integration));

        $boards = [];
        try {
            foreach ($miroApiClient->iterateBoards($integration->getExternalAccountId()) as $miroBoard) {
                $boards[] = new RemoteSource($miroBoard->id, $miroBoard->name, $miroBoard->viewLink);
            }
        } catch (MiroAuthorizationException $exception) {
            $integration->markNeedsReauth($exception->getMessage());
            $this->entityManager->flush();

            throw new ReAuthenticationNeededException($integration->getId()->toRfc4122(), 0, $exception);
        }

        return $boards;
    }

    public function findIntegration(Larp $larp): ?LarpIntegration
    {
        return $this->larpIntegrationRepository->findOneBy([
            'larp' => $larp,
            'provider' => LarpIntegrationProvider::Miro,
        ]);
    }
}
