<?php

namespace App\Domain\Integrations\Service;

use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Service\Capability\IntegrationProviderInterface;
use App\Domain\Integrations\Service\Exceptions\UnsupportedCapability;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Contracts\Service\ServiceProviderInterface;

readonly class IntegrationRegistry
{
    /**
     * @param ServiceProviderInterface<IntegrationProviderInterface> $integrationProviders
     */
    public function __construct(
        #[AutowireLocator(IntegrationProviderInterface::class, defaultIndexMethod: 'providerKey')]
        private ServiceProviderInterface $integrationProviders,
    ) {
    }

    /**
     * @template T of object
     * @param class-string<T> $capability
     * @return T
     * @throws UnsupportedCapability
     */
    public function capability(LarpIntegration $integration, string $capability): object
    {
        return $this->providerCapability($integration->getProvider(), $capability);
    }

    /**
     * @template T of object
     * @param class-string<T> $capability
     * @return T
     * @throws UnsupportedCapability
     */
    public function providerCapability(LarpIntegrationProvider $provider, string $capability): object
    {
        if (!$this->supports($provider, $capability)) {
            throw UnsupportedCapability::forProvider($provider->value, $capability);
        }

        $integrationProvider = $this->integrationProviders->get($provider->value);
        if (!$integrationProvider instanceof $capability) {
            throw UnsupportedCapability::forProvider($provider->value, $capability);
        }

        return $integrationProvider;
    }

    /**
     * @param class-string $capability
     */
    public function supports(LarpIntegrationProvider $provider, string $capability): bool
    {
        $providedServices = $this->integrationProviders->getProvidedServices();
        $serviceClass = $providedServices[$provider->value] ?? null;
        if ($serviceClass === null) {
            return false;
        }

        return is_a(ltrim($serviceClass, '?'), $capability, true);
    }

    /**
     * @param class-string $capability
     * @return list<LarpIntegrationProvider>
     */
    public function providersSupporting(string $capability): array
    {
        return array_values(array_filter(
            LarpIntegrationProvider::cases(),
            fn (LarpIntegrationProvider $provider): bool => $this->supports($provider, $capability),
        ));
    }
}
