<?php

namespace App\Domain\Integrations\Controller\Backoffice;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\Tag;
use App\Domain\Core\Repository\TagRepository;
use App\Domain\Integrations\Discord\Client\Exception\DiscordApiException;
use App\Domain\Integrations\Discord\Picker\DiscordChannelPicker;
use App\Domain\Integrations\Discord\Picker\DiscordSourceConfig;
use App\Domain\Integrations\Entity\Enum\ExternalSourceVisibility;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Repository\LarpIntegrationRepository;
use App\Domain\Integrations\Security\Voter\IntegrationSettingsVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/larp/{larp}/integration/discord', name: 'backoffice_larp_discord_')]
class DiscordIntegrationController extends AbstractController
{
    public const CHANNELS_CSRF_TOKEN_ID = 'discord_channels';

    public function __construct(
        private readonly LarpIntegrationRepository $larpIntegrationRepository,
        private readonly DiscordChannelPicker $discordChannelPicker,
        private readonly TagRepository $tagRepository,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route('/channels', name: 'channels', methods: ['GET'])]
    public function channels(Larp $larp): Response
    {
        $this->denyAccessUnlessGranted(IntegrationSettingsVoter::MODIFY, $larp);
        $integration = $this->discordIntegration($larp);

        try {
            $channels = $this->discordChannelPicker->pickableChannels($integration);
        } catch (DiscordApiException) {
            $this->addFlash('danger', $this->translator->trans('channels.unavailable', [], 'discord'));

            return $this->redirectToDiscordPage($larp);
        }

        $sourceConfigs = [];
        foreach ($this->discordChannelPicker->watchedSources($integration) as $channelId => $source) {
            $sourceConfigs[$channelId] = DiscordSourceConfig::fromArray($source->getConfig());
        }

        return $this->render('domain/integrations/discord/channels.html.twig', [
            'larp' => $larp,
            'integration' => $integration,
            'channels' => $channels,
            'sourceConfigs' => $sourceConfigs,
            'defaultSourceConfig' => new DiscordSourceConfig(),
            'visibilities' => ExternalSourceVisibility::cases(),
            'targetTypes' => DiscordChannelPicker::SELECTABLE_TARGET_TYPES,
            'larpTags' => $this->larpTags($larp),
            'csrfTokenId' => self::CHANNELS_CSRF_TOKEN_ID,
        ]);
    }

    #[Route('/channels', name: 'channels_save', methods: ['POST'])]
    public function saveChannels(Larp $larp, Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted(IntegrationSettingsVoter::MODIFY, $larp);
        $integration = $this->discordIntegration($larp);

        if (!$this->isCsrfTokenValid(self::CHANNELS_CSRF_TOKEN_ID, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        try {
            $channels = $this->discordChannelPicker->pickableChannels($integration);
        } catch (DiscordApiException) {
            $this->addFlash('danger', $this->translator->trans('channels.unavailable', [], 'discord'));

            return $this->redirectToDiscordPage($larp);
        }

        $this->discordChannelPicker->save($integration, $channels, $request->request->all('channels'), $this->larpTags($larp));
        $this->addFlash('success', $this->translator->trans('channels.saved', [], 'discord'));

        return $this->redirectToRoute('backoffice_larp_discord_channels', ['larp' => $larp->getId()->toRfc4122()]);
    }

    private function discordIntegration(Larp $larp): LarpIntegration
    {
        $integration = $this->larpIntegrationRepository->findOneByLarpAndProvider($larp, LarpIntegrationProvider::Discord);
        if (!$integration instanceof LarpIntegration) {
            throw $this->createNotFoundException('Discord is not connected to this LARP.');
        }

        return $integration;
    }

    /**
     * @return list<Tag>
     */
    private function larpTags(Larp $larp): array
    {
        return array_values($this->tagRepository->findBy(['larp' => $larp], ['title' => 'ASC']));
    }

    private function redirectToDiscordPage(Larp $larp): RedirectResponse
    {
        return $this->redirectToRoute('backoffice_larp_integrations_sources', [
            'larp' => $larp->getId()->toRfc4122(),
            'provider' => LarpIntegrationProvider::Discord->value,
        ]);
    }
}
