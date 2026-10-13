<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Integrations;

use App\Domain\Integrations\Discord\Client\DiscordClient;
use App\Domain\Integrations\Discord\Client\DiscordSleeper;
use App\Domain\Integrations\Discord\DiscordIntegrationProvider;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Repository\ExternalReferenceRepository;
use App\Domain\Integrations\Repository\ExternalSourceRepository;
use App\Domain\Integrations\Repository\LarpIntegrationRepository;
use App\Domain\Integrations\Service\Capability\AccessTokenProviderInterface;
use App\Domain\Integrations\Service\Capability\BoardSourceIntegrationInterface;
use App\Domain\Integrations\Service\Capability\ConnectableIntegrationInterface;
use App\Domain\Integrations\Service\Capability\CredentialProviderInterface;
use App\Domain\Integrations\Service\Capability\FileSourceIntegrationInterface;
use App\Domain\Integrations\Service\Credential\BotCredentialProvider;
use App\Domain\Integrations\Service\Exceptions\UnsupportedCapability;
use App\Domain\Integrations\Service\Google\GoogleIntegrationService;
use App\Domain\Integrations\Service\IntegrationRegistry;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpClient\MockHttpClient;

class IntegrationRegistryTest extends TestCase
{
    public function testReturnsTheProviderServiceForASupportedCapability(): void
    {
        $registry = $this->createRegistry();
        $integration = new LarpIntegration();
        $integration->setProvider(LarpIntegrationProvider::Discord);

        $credentialProvider = $registry->capability($integration, CredentialProviderInterface::class);

        $this->assertInstanceOf(DiscordIntegrationProvider::class, $credentialProvider);
        $this->assertSame('Bot discord-bot-token', $credentialProvider->authorizationHeader($integration));
    }

    public function testThrowsWhenTheProviderLacksTheCapability(): void
    {
        $registry = $this->createRegistry();
        $integration = new LarpIntegration();
        $integration->setProvider(LarpIntegrationProvider::Discord);

        $this->expectException(UnsupportedCapability::class);

        $registry->capability($integration, BoardSourceIntegrationInterface::class);
    }

    public function testThrowsForAProviderWithoutAnyRegisteredService(): void
    {
        $registry = $this->createRegistry();
        $integration = new LarpIntegration();
        $integration->setProvider(LarpIntegrationProvider::Facebook);

        $this->expectException(UnsupportedCapability::class);

        $registry->capability($integration, ConnectableIntegrationInterface::class);
    }

    public function testListsOnlyProvidersSupportingACapabilityWithoutInstantiatingThem(): void
    {
        $registry = $this->createRegistry();

        $this->assertSame(
            [LarpIntegrationProvider::Google, LarpIntegrationProvider::Discord],
            $registry->providersSupporting(ConnectableIntegrationInterface::class)
        );
        $this->assertSame([LarpIntegrationProvider::Google], $registry->providersSupporting(FileSourceIntegrationInterface::class));
        $this->assertSame([LarpIntegrationProvider::Google], $registry->providersSupporting(AccessTokenProviderInterface::class));
        $this->assertSame(
            [LarpIntegrationProvider::Google, LarpIntegrationProvider::Discord],
            $registry->providersSupporting(CredentialProviderInterface::class)
        );
        $this->assertFalse($registry->supports(LarpIntegrationProvider::Facebook, CredentialProviderInterface::class));
    }

    private function createDiscordProvider(): DiscordIntegrationProvider
    {
        return new DiscordIntegrationProvider(
            new BotCredentialProvider('discord-bot-token'),
            new DiscordClient(new MockHttpClient(), $this->createMock(DiscordSleeper::class)),
            $this->createMock(LarpIntegrationRepository::class),
            $this->createMock(ExternalSourceRepository::class),
            $this->createMock(ExternalReferenceRepository::class),
            $this->createMock(EntityManagerInterface::class),
            new NullLogger(),
            'discord-client-id',
            'discord-client-secret',
        );
    }

    private function createRegistry(): IntegrationRegistry
    {
        return new IntegrationRegistry(new ServiceLocator([
            LarpIntegrationProvider::Google->value => fn (): GoogleIntegrationService => $this->fail('Google must not be instantiated to answer capability questions.'),
            LarpIntegrationProvider::Discord->value => fn (): DiscordIntegrationProvider => $this->createDiscordProvider(),
        ], [
            LarpIntegrationProvider::Google->value => GoogleIntegrationService::class,
            LarpIntegrationProvider::Discord->value => DiscordIntegrationProvider::class,
        ]));
    }
}
