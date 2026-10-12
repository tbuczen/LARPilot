<?php

namespace App\Domain\Integrations\Controller\Backoffice;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Entity\Larp;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\Enum\ReferenceRole;
use App\Domain\Integrations\Entity\ExternalReference;
use App\Domain\Integrations\Entity\ExternalSource;
use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Repository\ExternalItemRepository;
use App\Domain\Integrations\Repository\ExternalSourceRepository;
use App\Domain\Integrations\Repository\LarpIntegrationRepository;
use App\Domain\Integrations\Security\Voter\IntegrationSettingsVoter;
use App\Domain\Integrations\Security\Voter\StorySourceVoter;
use App\Domain\Integrations\Service\Capability\ConnectableIntegrationInterface;
use App\Domain\Integrations\Service\Hub\IntegrationCard;
use App\Domain\Integrations\Service\Hub\IntegrationHubOverview;
use App\Domain\Integrations\Service\IntegrationRegistry;
use App\Domain\Integrations\Service\StorySourceLinkService;
use App\Domain\Integrations\Service\Sync\SourceSyncRequesterInterface;
use App\Domain\StoryObject\Entity\StoryObject;
use App\Domain\StoryObject\Repository\StoryObjectRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/larp/{larp}/integrations', name: 'backoffice_larp_integrations_')]
class IntegrationHubController extends BaseController
{
    public const DISCONNECT_CSRF_TOKEN = 'delete';

    public const LINK_CSRF_TOKEN = 'integration_item_link';

    private const TAB_SOURCES = 'sources';

    private const TAB_BROWSE = 'browse';

    private const TAB_ACTIVITY = 'activity';

    private const TAB_MAPPINGS = 'mappings';

    #[Route('', name: 'hub', methods: ['GET'])]
    public function hub(Larp $larp, IntegrationHubOverview $hubOverview): Response
    {
        $this->denyAccessUnlessGranted(IntegrationSettingsVoter::MODIFY, $larp);

        return $this->render('domain/integrations/hub/index.html.twig', [
            'larp' => $larp,
            'cards' => $hubOverview->cards($larp),
        ]);
    }

    #[Route('/{provider}', name: 'sources', methods: ['GET'])]
    public function sources(
        Larp $larp,
        LarpIntegrationProvider $provider,
        IntegrationHubOverview $hubOverview,
        ExternalSourceRepository $sourceRepository,
        ?SourceSyncRequesterInterface $syncRequester = null,
    ): Response {
        $this->denyAccessUnlessGranted(IntegrationSettingsVoter::MODIFY, $larp);
        $card = $this->connectedCard($larp, $provider, $hubOverview);
        if (!$card instanceof IntegrationCard) {
            return $this->redirectToHub($larp);
        }

        return $this->renderTab(self::TAB_SOURCES, $larp, $card, [
            'sources' => $sourceRepository->findForIntegration($card->connectedIntegration()),
            'canSync' => $syncRequester instanceof SourceSyncRequesterInterface,
        ]);
    }

    #[Route('/{provider}/browse', name: 'browse', methods: ['GET'])]
    public function browse(
        Request $request,
        Larp $larp,
        LarpIntegrationProvider $provider,
        IntegrationHubOverview $hubOverview,
        ExternalItemRepository $itemRepository,
    ): Response {
        $this->denyAccessUnlessGranted(IntegrationSettingsVoter::MODIFY, $larp);
        $card = $this->connectedCard($larp, $provider, $hubOverview);
        if (!$card instanceof IntegrationCard) {
            return $this->redirectToHub($larp);
        }

        $searchTerm = $request->query->getString('q');

        return $this->renderTab(self::TAB_BROWSE, $larp, $card, [
            'searchTerm' => $searchTerm,
            'items' => $itemRepository->searchForIntegration($card->connectedIntegration(), $searchTerm),
            'roles' => ReferenceRole::cases(),
        ]);
    }

    #[Route('/{provider}/activity', name: 'activity', methods: ['GET'])]
    public function activity(
        Larp $larp,
        LarpIntegrationProvider $provider,
        IntegrationHubOverview $hubOverview,
        ExternalSourceRepository $sourceRepository,
    ): Response {
        $this->denyAccessUnlessGranted(IntegrationSettingsVoter::MODIFY, $larp);
        $card = $this->connectedCard($larp, $provider, $hubOverview);
        if (!$card instanceof IntegrationCard) {
            return $this->redirectToHub($larp);
        }

        $sources = $sourceRepository->findForIntegration($card->connectedIntegration());
        usort(
            $sources,
            static fn (ExternalSource $left, ExternalSource $right): int => ($right->getLastSyncedAt()?->getTimestamp() ?? 0) <=> ($left->getLastSyncedAt()?->getTimestamp() ?? 0),
        );

        return $this->renderTab(self::TAB_ACTIVITY, $larp, $card, [
            'sources' => $sources,
        ]);
    }

    #[Route('/{provider}/mappings', name: 'mappings', methods: ['GET'])]
    public function mappings(
        Larp $larp,
        LarpIntegrationProvider $provider,
        IntegrationHubOverview $hubOverview,
    ): Response {
        $this->denyAccessUnlessGranted(IntegrationSettingsVoter::MODIFY, $larp);
        if (!$provider->hasMappings()) {
            throw $this->createNotFoundException();
        }

        $card = $this->connectedCard($larp, $provider, $hubOverview);
        if (!$card instanceof IntegrationCard) {
            return $this->redirectToHub($larp);
        }

        return $this->renderTab(self::TAB_MAPPINGS, $larp, $card);
    }

    #[Route('/{provider}/disconnect', name: 'disconnect', methods: ['POST'])]
    public function disconnect(
        Request $request,
        Larp $larp,
        LarpIntegrationProvider $provider,
        LarpIntegrationRepository $integrationRepository,
        IntegrationRegistry $integrationRegistry,
    ): RedirectResponse {
        $this->denyAccessUnlessGranted(IntegrationSettingsVoter::MODIFY, $larp);
        $this->assertCsrfToken(self::DISCONNECT_CSRF_TOKEN, $request);

        $integration = $integrationRepository->findOneBy(['larp' => $larp, 'provider' => $provider]);
        if (!$integration instanceof LarpIntegration) {
            throw $this->createNotFoundException();
        }

        if ($integrationRegistry->supports($provider, ConnectableIntegrationInterface::class)) {
            $integrationRegistry->providerCapability($provider, ConnectableIntegrationInterface::class)->disconnect($integration);
        } else {
            $integrationRepository->remove($integration);
        }

        $this->addFlash('success', $this->translator->trans('integration_hub.disconnected', ['%provider%' => $provider->getLabel()]));

        return $this->redirectToHub($larp);
    }

    #[Route('/{provider}/sources/{source}/toggle', name: 'source_toggle', methods: ['POST'])]
    public function toggleSource(
        Request $request,
        Larp $larp,
        LarpIntegrationProvider $provider,
        string $source,
        ExternalSourceRepository $sourceRepository,
    ): RedirectResponse {
        $this->denyAccessUnlessGranted(IntegrationSettingsVoter::MODIFY, $larp);
        $externalSource = $this->findSourceOfProvider($source, $larp, $provider, $sourceRepository);
        $this->assertCsrfToken('integration_source_toggle_' . $externalSource->getId()->toRfc4122(), $request);

        $externalSource->setEnabled($request->request->getBoolean('enabled'));
        $sourceRepository->save($externalSource);

        $this->addFlash('success', $this->translator->trans(
            $externalSource->isEnabled() ? 'integration_hub.source.enabled_flash' : 'integration_hub.source.disabled_flash',
            ['%source%' => $externalSource->getTitle()],
        ));

        return $this->redirectToTab(self::TAB_SOURCES, $larp, $provider);
    }

    #[Route('/{provider}/sources/{source}/sync', name: 'source_sync', methods: ['POST'])]
    public function syncSource(
        Request $request,
        Larp $larp,
        LarpIntegrationProvider $provider,
        string $source,
        ExternalSourceRepository $sourceRepository,
        ?SourceSyncRequesterInterface $syncRequester = null,
    ): RedirectResponse {
        $this->denyAccessUnlessGranted(IntegrationSettingsVoter::MODIFY, $larp);
        $externalSource = $this->findSourceOfProvider($source, $larp, $provider, $sourceRepository);
        $this->assertCsrfToken('integration_source_sync_' . $externalSource->getId()->toRfc4122(), $request);

        if (!$syncRequester instanceof SourceSyncRequesterInterface) {
            throw $this->createNotFoundException();
        }

        $syncRequester->requestSync($externalSource);
        $this->addFlash('success', $this->translator->trans('integration_hub.source.sync_requested', ['%source%' => $externalSource->getTitle()]));

        return $this->redirectToTab(self::TAB_SOURCES, $larp, $provider);
    }

    #[Route('/{provider}/browse/story-objects', name: 'story_object_search', methods: ['GET'])]
    public function searchStoryObjects(
        Request $request,
        Larp $larp,
        LarpIntegrationProvider $provider,
        StoryObjectRepository $storyObjectRepository,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(IntegrationSettingsVoter::MODIFY, $larp);

        $storyObjects = $storyObjectRepository->searchByTitle($larp, trim($request->query->getString('q')), 20);

        $results = [];
        foreach ($storyObjects as $storyObject) {
            $results[] = [
                'id' => $storyObject->getId()->toRfc4122(),
                'title' => $storyObject->getTitle(),
                'kind' => $this->translator->trans($storyObject::getTargetType()->value),
            ];
        }

        return new JsonResponse(['items' => $results]);
    }

    #[Route('/{provider}/browse/link', name: 'item_link', methods: ['POST'])]
    public function linkItem(
        Request $request,
        Larp $larp,
        LarpIntegrationProvider $provider,
        StoryObjectRepository $storyObjectRepository,
        ExternalItemRepository $itemRepository,
        StorySourceLinkService $linkService,
    ): RedirectResponse {
        $this->denyAccessUnlessGranted(IntegrationSettingsVoter::MODIFY, $larp);
        $this->assertCsrfToken(self::LINK_CSRF_TOKEN, $request);

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $storyObject = $this->findStoryObjectOfLarp($request->request->getString('storyObject'), $larp, $storyObjectRepository);
        $this->denyAccessUnlessGranted(StorySourceVoter::LINK, $storyObject);

        $itemId = $request->request->getString('item');
        $item = $itemRepository->findOneForLarp($larp, $itemId);
        if ($item === null || $item->getSource()->getIntegration()->getProvider() !== $provider) {
            throw $this->createNotFoundException();
        }

        $role = ReferenceRole::tryFrom($request->request->getString('role')) ?? ReferenceRole::Mention;
        $reference = $linkService->linkCachedItem($storyObject, $itemId, $role, $user);

        if ($reference instanceof ExternalReference) {
            $this->addFlash('success', $this->translator->trans('integration_hub.browse.linked', ['%storyObject%' => $storyObject->getTitle()]));
        } else {
            $this->addFlash('error', $this->translator->trans('story_source.item_not_found'));
        }

        return $this->redirectToTab(self::TAB_BROWSE, $larp, $provider);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function renderTab(string $tab, Larp $larp, IntegrationCard $card, array $parameters = []): Response
    {
        return $this->render('domain/integrations/hub/' . $tab . '.html.twig', array_merge([
            'larp' => $larp,
            'card' => $card,
            'provider' => $card->provider,
            'integration' => $card->integration,
            'currentTab' => $tab,
        ], $parameters));
    }

    private function connectedCard(Larp $larp, LarpIntegrationProvider $provider, IntegrationHubOverview $hubOverview): ?IntegrationCard
    {
        $card = $hubOverview->card($larp, $provider);
        if (!$card instanceof IntegrationCard || !$card->integration instanceof LarpIntegration) {
            return null;
        }

        return $card;
    }

    private function findSourceOfProvider(
        string $sourceId,
        Larp $larp,
        LarpIntegrationProvider $provider,
        ExternalSourceRepository $sourceRepository,
    ): ExternalSource {
        if (!Uuid::isValid($sourceId)) {
            throw $this->createNotFoundException();
        }

        $externalSource = $sourceRepository->find($sourceId);
        if (!$externalSource instanceof ExternalSource) {
            throw $this->createNotFoundException();
        }

        $integration = $externalSource->getIntegration();
        if (!$integration->getLarp()->getId()->equals($larp->getId()) || $integration->getProvider() !== $provider) {
            throw $this->createNotFoundException();
        }

        return $externalSource;
    }

    private function findStoryObjectOfLarp(string $storyObjectId, Larp $larp, StoryObjectRepository $storyObjectRepository): StoryObject
    {
        if (!Uuid::isValid($storyObjectId)) {
            throw $this->createNotFoundException();
        }

        $storyObject = $storyObjectRepository->find($storyObjectId);
        if (!$storyObject instanceof StoryObject || $storyObject->getLarp()?->getId()->equals($larp->getId()) !== true) {
            throw $this->createNotFoundException();
        }

        return $storyObject;
    }

    private function assertCsrfToken(string $tokenId, Request $request): void
    {
        if ($this->isCsrfTokenValid($tokenId, $request->request->getString('_token'))) {
            return;
        }

        throw $this->createAccessDeniedException('Invalid CSRF token.');
    }

    private function redirectToHub(Larp $larp): RedirectResponse
    {
        return $this->redirectToRoute('backoffice_larp_integrations_hub', ['larp' => $larp->getId()->toRfc4122()]);
    }

    private function redirectToTab(string $tab, Larp $larp, LarpIntegrationProvider $provider): RedirectResponse
    {
        return $this->redirectToRoute('backoffice_larp_integrations_' . $tab, [
            'larp' => $larp->getId()->toRfc4122(),
            'provider' => $provider->value,
        ]);
    }
}
