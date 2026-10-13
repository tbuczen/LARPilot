<?php

namespace App\Domain\Integrations\Service\Hub;

use App\Domain\Core\Entity\Larp;
use App\Domain\Integrations\Entity\Enum\ExternalSourceSyncStatus;
use App\Domain\Integrations\Entity\Enum\IntegrationStatus;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\ExternalSource;
use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Repository\ExternalSourceRepository;
use App\Domain\Integrations\Repository\LarpIntegrationRepository;
use App\Domain\Integrations\Service\Capability\ConnectableIntegrationInterface;
use App\Domain\Integrations\Service\Google\GoogleIntegrationService;
use App\Domain\Integrations\Service\IntegrationRegistry;

readonly class IntegrationHubOverview
{
    private const TOKEN_EXPIRY_MARGIN = '+30 seconds';

    public function __construct(
        private IntegrationRegistry $integrationRegistry,
        private LarpIntegrationRepository $larpIntegrationRepository,
        private ExternalSourceRepository $externalSourceRepository,
    ) {
    }

    /**
     * @return list<IntegrationCard>
     */
    public function cards(Larp $larp): array
    {
        $integrationsByProvider = [];
        foreach ($this->larpIntegrationRepository->findAllByLarp($larp) as $integration) {
            $integrationsByProvider[$integration->getProvider()->value] = $integration;
        }

        $sourcesByIntegration = [];
        foreach ($this->externalSourceRepository->findForLarp($larp) as $source) {
            $sourcesByIntegration[$source->getIntegration()->getId()->toRfc4122()][] = $source;
        }

        $cards = [];
        foreach (LarpIntegrationProvider::cases() as $provider) {
            $integration = $integrationsByProvider[$provider->value] ?? null;
            $connectable = $this->integrationRegistry->supports($provider, ConnectableIntegrationInterface::class);
            if (!$connectable && !$integration instanceof LarpIntegration) {
                continue;
            }

            $sources = $integration instanceof LarpIntegration
                ? $sourcesByIntegration[$integration->getId()->toRfc4122()] ?? []
                : [];

            $cards[] = $this->buildCard($provider, $integration, $connectable, $sources);
        }

        return $cards;
    }

    public function card(Larp $larp, LarpIntegrationProvider $provider): ?IntegrationCard
    {
        foreach ($this->cards($larp) as $card) {
            if ($card->provider === $provider) {
                return $card;
            }
        }

        return null;
    }

    public function countNeedingAttention(Larp $larp): int
    {
        return count(array_filter(
            $this->cards($larp),
            static fn (IntegrationCard $card): bool => $card->state->needsAttention(),
        ));
    }

    /**
     * @param ExternalSource[] $sources
     */
    public function state(?LarpIntegration $integration, array $sources): IntegrationHubState
    {
        if (!$integration instanceof LarpIntegration || $integration->getStatus() === IntegrationStatus::Disabled) {
            return IntegrationHubState::NotConnected;
        }

        if ($integration->getStatus() === IntegrationStatus::NeedsReauth || $this->hasExpiredUserToken($integration) || $this->requiresScopeUpgrade($integration)) {
            return IntegrationHubState::NeedsReconnect;
        }

        if ($integration->getStatus() === IntegrationStatus::Error) {
            return IntegrationHubState::Error;
        }

        foreach ($sources as $source) {
            if ($source->isEnabled() && $source->getLastSyncStatus() === ExternalSourceSyncStatus::Failed) {
                return IntegrationHubState::Error;
            }
        }

        return IntegrationHubState::Connected;
    }

    /**
     * @param ExternalSource[] $sources
     */
    private function buildCard(
        LarpIntegrationProvider $provider,
        ?LarpIntegration $integration,
        bool $connectable,
        array $sources,
    ): IntegrationCard {
        return new IntegrationCard(
            provider: $provider,
            state: $this->state($integration, $sources),
            integration: $integration,
            connectable: $connectable,
            accountLabel: $integration instanceof LarpIntegration ? $this->accountLabel($integration) : null,
            lastSyncedAt: $this->lastSyncedAt($sources),
            sourceCount: count($sources),
        );
    }

    private function hasExpiredUserToken(LarpIntegration $integration): bool
    {
        if (!$integration->getConnectionType()->requiresUserToken()) {
            return false;
        }

        if ($integration->getRefreshToken() !== null && $integration->getRefreshToken() !== '') {
            return false;
        }

        $expiresAt = $integration->getExpiresAt();
        if (!$expiresAt instanceof \DateTimeInterface) {
            return false;
        }

        return $expiresAt < new \DateTimeImmutable(self::TOKEN_EXPIRY_MARGIN);
    }

    private function requiresScopeUpgrade(LarpIntegration $integration): bool
    {
        return $integration->getProvider() === LarpIntegrationProvider::Google
            && GoogleIntegrationService::requiresScopeUpgrade($integration);
    }

    private function accountLabel(LarpIntegration $integration): ?string
    {
        $configuredLabel = $integration->getConfig()['accountLabel'] ?? null;
        if (is_string($configuredLabel) && $configuredLabel !== '') {
            return $configuredLabel;
        }

        return $integration->getExternalAccountId() ?? $integration->getOwner();
    }

    /**
     * @param ExternalSource[] $sources
     */
    private function lastSyncedAt(array $sources): ?\DateTimeImmutable
    {
        $lastSyncedAt = null;
        foreach ($sources as $source) {
            $sourceSyncedAt = $source->getLastSyncedAt();
            if ($sourceSyncedAt instanceof \DateTimeImmutable && ($lastSyncedAt === null || $sourceSyncedAt > $lastSyncedAt)) {
                $lastSyncedAt = $sourceSyncedAt;
            }
        }

        return $lastSyncedAt;
    }
}
