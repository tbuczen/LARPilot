<?php

namespace App\Twig;

use App\Domain\Core\Entity\Larp;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Repository\ExternalItemRepository;
use App\Domain\Integrations\Repository\LarpIntegrationRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class IntegrationInboxExtension extends AbstractExtension
{
    /** @var array<string, int|null> */
    private array $unreviewedCounts = [];

    public function __construct(
        private readonly LarpIntegrationRepository $integrationRepository,
        private readonly ExternalItemRepository $itemRepository,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('discord_inbox_count', $this->discordInboxCount(...)),
        ];
    }

    public function discordInboxCount(Larp $larp): ?int
    {
        $larpId = $larp->getId()->toRfc4122();
        if (array_key_exists($larpId, $this->unreviewedCounts)) {
            return $this->unreviewedCounts[$larpId];
        }

        $integration = $this->integrationRepository->findOneByLarpAndProvider($larp, LarpIntegrationProvider::Discord);

        return $this->unreviewedCounts[$larpId] = $integration instanceof LarpIntegration
            ? $this->itemRepository->countUnreviewedInbox($integration)
            : null;
    }
}
