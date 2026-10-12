<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Integrations\Google;

use App\Domain\Integrations\Entity\Enum\IntegrationStatus;
use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Service\Exceptions\ReAuthenticationNeededException;
use App\Domain\Integrations\Service\Google\GoogleClientManager;
use App\Domain\Integrations\Service\Google\GoogleOAuthClientFactory;
use Doctrine\ORM\EntityManagerInterface;
use Google\Client;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GoogleClientManagerTest extends TestCase
{
    public function testValidTokenIsNotRefreshed(): void
    {
        $integration = $this->createIntegration(new \DateTimeImmutable('+1 hour'));
        $googleClient = $this->createGoogleClient();
        $googleClient->expects($this->never())->method('fetchAccessTokenWithRefreshToken');
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('flush');

        $clientManager = $this->createClientManager($googleClient, $entityManager);
        $authorizedClient = $clientManager->getClientForIntegration($integration);

        $this->assertSame('stored-access-token', $authorizedClient->getAccessToken()['access_token']);
        $this->assertFalse($authorizedClient->isAccessTokenExpired());
        $this->assertSame(IntegrationStatus::Active, $integration->getStatus());
    }

    public function testExpiredTokenIsRefreshedAndPersisted(): void
    {
        $integration = $this->createIntegration(new \DateTimeImmutable('-1 hour'));
        $googleClient = $this->createGoogleClient();
        $googleClient->expects($this->once())
            ->method('fetchAccessTokenWithRefreshToken')
            ->with('stored-refresh-token')
            ->willReturn(['access_token' => 'refreshed-access-token', 'expires_in' => 3600]);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $clientManager = $this->createClientManager($googleClient, $entityManager);
        $authorizedClient = $clientManager->getClientForIntegration($integration);

        $this->assertSame('refreshed-access-token', $integration->getAccessToken());
        $this->assertSame('stored-refresh-token', $integration->getRefreshToken());
        $this->assertGreaterThan(new \DateTimeImmutable('+50 minutes'), $integration->getExpiresAt());
        $this->assertSame(IntegrationStatus::Active, $integration->getStatus());
        $this->assertFalse($authorizedClient->isAccessTokenExpired());
    }

    public function testRefreshFailureMarksIntegrationAsNeedingReauth(): void
    {
        $integration = $this->createIntegration(new \DateTimeImmutable('-1 hour'));
        $googleClient = $this->createGoogleClient();
        $googleClient->method('fetchAccessTokenWithRefreshToken')
            ->willReturn(['error' => 'invalid_grant', 'error_description' => 'Token has been expired or revoked.']);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $clientManager = $this->createClientManager($googleClient, $entityManager);

        try {
            $clientManager->getClientForIntegration($integration);
            $this->fail('Expected re-authentication to be required.');
        } catch (ReAuthenticationNeededException) {
        }

        $this->assertSame(IntegrationStatus::NeedsReauth, $integration->getStatus());
        $this->assertSame('Token has been expired or revoked.', $integration->getLastError());
        $this->assertNotNull($integration->getLastCheckedAt());
    }

    public function testExpiredTokenWithoutRefreshTokenMarksIntegrationAsNeedingReauth(): void
    {
        $integration = $this->createIntegration(new \DateTimeImmutable('-1 hour'));
        $integration->setRefreshToken(null);
        $googleClient = $this->createGoogleClient();
        $googleClient->expects($this->never())->method('fetchAccessTokenWithRefreshToken');
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $clientManager = $this->createClientManager($googleClient, $entityManager);

        $this->expectException(ReAuthenticationNeededException::class);
        try {
            $clientManager->getClientForIntegration($integration);
        } finally {
            $this->assertSame(IntegrationStatus::NeedsReauth, $integration->getStatus());
        }
    }

    private function createIntegration(\DateTimeImmutable $expiresAt): LarpIntegration
    {
        $integration = new LarpIntegration();
        $integration->setAccessToken('stored-access-token');
        $integration->setRefreshToken('stored-refresh-token');
        $integration->setExpiresAt($expiresAt);

        return $integration;
    }

    private function createGoogleClient(): Client&MockObject
    {
        return $this->getMockBuilder(Client::class)
            ->setConstructorArgs([[]])
            ->onlyMethods(['fetchAccessTokenWithRefreshToken'])
            ->getMock();
    }

    private function createClientManager(Client $googleClient, EntityManagerInterface $entityManager): GoogleClientManager
    {
        $oauthClientFactory = $this->createMock(GoogleOAuthClientFactory::class);
        $oauthClientFactory->method('create')->willReturn($googleClient);

        return new GoogleClientManager('/dev/null', $entityManager, $oauthClientFactory);
    }
}
