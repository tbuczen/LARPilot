<?php

namespace App\Domain\Integrations\Controller\Backoffice;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Entity\Larp;
use App\Domain\Integrations\Discord\Picker\DiscordChannelPicker;
use App\Domain\Integrations\Entity\Enum\ExternalItemReviewStatus;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\ExternalItem;
use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Form\Filter\IntegrationInboxFilterType;
use App\Domain\Integrations\Repository\ExternalItemRepository;
use App\Domain\Integrations\Security\Voter\IntegrationSettingsVoter;
use App\Domain\Integrations\Security\Voter\StorySourceVoter;
use App\Domain\Integrations\Service\Hub\IntegrationHubOverview;
use App\Domain\Integrations\Service\Suggestion\LinkSuggestionAggregator;
use App\Domain\Integrations\Service\Triage\ExternalItemTriage;
use App\Domain\StoryObject\Entity\Enum\TargetType;
use App\Domain\StoryObject\Entity\StoryObject;
use App\Domain\StoryObject\Repository\StoryObjectRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/larp/{larp}/integration/discord/inbox', name: 'backoffice_larp_discord_inbox')]
class DiscordInboxController extends BaseController
{
    public const CSRF_TOKEN_ID = 'discord_inbox';

    public const MAX_BULK_ITEMS = 100;

    #[Route('', name: '', methods: ['GET'])]
    public function inbox(
        Request $request,
        Larp $larp,
        ExternalItemRepository $itemRepository,
        LinkSuggestionAggregator $suggestionAggregator,
        IntegrationHubOverview $hubOverview,
    ): Response {
        $this->denyAccessUnlessGranted(StorySourceVoter::BROWSE, $larp);
        $integration = $this->discordIntegration($larp);

        $suggestionAggregator->refresh($larp, $itemRepository->findUnreviewedInboxWithoutSuggestions($integration));

        $filterForm = $this->createForm(IntegrationInboxFilterType::class, ['status' => ExternalItemReviewStatus::Unreviewed->value], [
            'integration' => $integration,
        ]);
        $filterForm->handleRequest($request);

        $queryBuilder = $itemRepository->createInboxQueryBuilder($integration);
        $this->filterBuilderUpdater->addFilterConditions($filterForm, $queryBuilder);
        $pagination = $this->getPagination($queryBuilder, $request);

        $items = iterator_to_array($pagination->getItems(), false);
        $suggestions = $this->entityPreloader->preload($items, 'suggestions');
        $this->entityPreloader->preload($suggestions, 'storyObject');
        $this->entityPreloader->preload($suggestions, 'tag');

        $card = $this->isGranted(IntegrationSettingsVoter::MODIFY, $larp) ? $hubOverview->card($larp, LarpIntegrationProvider::Discord) : null;

        return $this->render('domain/integrations/discord/inbox.html.twig', [
            'larp' => $larp,
            'integration' => $integration,
            'card' => $card,
            'provider' => LarpIntegrationProvider::Discord,
            'currentTab' => 'inbox',
            'filterForm' => $filterForm->createView(),
            'pagination' => $pagination,
            'targetTypes' => DiscordChannelPicker::SELECTABLE_TARGET_TYPES,
            'csrfTokenId' => self::CSRF_TOKEN_ID,
            'returnQuery' => (string) $request->getQueryString(),
        ]);
    }

    #[Route('/story-objects', name: '_story_objects', methods: ['GET'])]
    public function searchStoryObjects(Request $request, Larp $larp, StoryObjectRepository $storyObjectRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted(StorySourceVoter::BROWSE, $larp);

        $results = [];
        foreach ($storyObjectRepository->searchByTitle($larp, trim($request->query->getString('q')), 20) as $storyObject) {
            $results[] = [
                'id' => $storyObject->getId()->toRfc4122(),
                'title' => $storyObject->getTitle(),
                'kind' => $this->translator->trans($storyObject::getTargetType()->value),
            ];
        }

        return new JsonResponse(['items' => $results]);
    }

    #[Route('/link', name: '_link', methods: ['POST'])]
    public function link(
        Request $request,
        Larp $larp,
        ExternalItemRepository $itemRepository,
        StoryObjectRepository $storyObjectRepository,
        ExternalItemTriage $triage,
    ): RedirectResponse {
        $user = $this->triagingUser($request, $larp);
        $integration = $this->discordIntegration($larp);

        $storyObject = $this->findStoryObjectOfLarp($request->request->getString('storyObject'), $larp, $storyObjectRepository);
        $this->denyAccessUnlessGranted(StorySourceVoter::LINK, $storyObject);

        $items = $this->submittedItems($request, $integration, $itemRepository);
        if ($items === []) {
            return $this->redirectToInboxWithNothingSelected($request, $larp);
        }

        $linkedCount = $triage->link($items, $storyObject, $user);
        $this->addFlash('success', $this->translator->trans('inbox.flash.linked', [
            '%count%' => $linkedCount,
            '%storyObject%' => $storyObject->getTitle(),
        ], 'discord'));

        return $this->redirectToInbox($request, $larp);
    }

    #[Route('/ignore', name: '_ignore', methods: ['POST'])]
    public function ignore(Request $request, Larp $larp, ExternalItemRepository $itemRepository, ExternalItemTriage $triage): RedirectResponse
    {
        $this->triagingUser($request, $larp);
        $integration = $this->discordIntegration($larp);

        $items = $this->submittedItems($request, $integration, $itemRepository);
        if ($items === []) {
            return $this->redirectToInboxWithNothingSelected($request, $larp);
        }

        $ignoredCount = $triage->ignore($items);
        $this->addFlash('success', $this->translator->trans('inbox.flash.ignored', ['%count%' => $ignoredCount], 'discord'));

        return $this->redirectToInbox($request, $larp);
    }

    #[Route('/restore', name: '_restore', methods: ['POST'])]
    public function restore(Request $request, Larp $larp, ExternalItemRepository $itemRepository, ExternalItemTriage $triage): RedirectResponse
    {
        $this->triagingUser($request, $larp);
        $integration = $this->discordIntegration($larp);

        $items = $this->submittedItems($request, $integration, $itemRepository);
        if ($items === []) {
            return $this->redirectToInboxWithNothingSelected($request, $larp);
        }

        $restoredCount = $triage->restore($items);
        $this->addFlash('success', $this->translator->trans('inbox.flash.restored', ['%count%' => $restoredCount], 'discord'));

        return $this->redirectToInbox($request, $larp);
    }

    #[Route('/create', name: '_create', methods: ['POST'])]
    public function createFromItem(Request $request, Larp $larp, ExternalItemRepository $itemRepository, ExternalItemTriage $triage): RedirectResponse
    {
        $user = $this->triagingUser($request, $larp);
        $integration = $this->discordIntegration($larp);

        $items = $itemRepository->findInboxItems($integration, [$request->request->getString('item')]);
        $item = $items[0] ?? null;
        if (!$item instanceof ExternalItem) {
            throw $this->createNotFoundException();
        }

        $targetType = TargetType::tryFrom($request->request->getString('targetType'));
        if (!in_array($targetType, DiscordChannelPicker::SELECTABLE_TARGET_TYPES, true)) {
            $this->addFlash('danger', $this->translator->trans('inbox.flash.choose_type', [], 'discord'));

            return $this->redirectToInbox($request, $larp);
        }

        $storyObject = $triage->createFromItem($item, $targetType, $user);
        $this->addFlash('success', $this->translator->trans('inbox.flash.created', [
            '%storyObject%' => $storyObject->getTitle(),
            '%type%' => $this->translator->trans('story_type.' . $targetType->value, [], 'discord'),
        ], 'discord'));

        return $this->redirectToInbox($request, $larp);
    }

    private function triagingUser(Request $request, Larp $larp): User
    {
        $this->denyAccessUnlessGranted(StorySourceVoter::BROWSE, $larp);
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_ID, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    private function discordIntegration(Larp $larp): LarpIntegration
    {
        $integration = $this->entityManager->getRepository(LarpIntegration::class)->findOneBy([
            'larp' => $larp,
            'provider' => LarpIntegrationProvider::Discord,
        ]);
        if (!$integration instanceof LarpIntegration) {
            throw $this->createNotFoundException('Discord is not connected to this LARP.');
        }

        return $integration;
    }

    /**
     * @return ExternalItem[]
     */
    private function submittedItems(Request $request, LarpIntegration $integration, ExternalItemRepository $itemRepository): array
    {
        $itemIds = array_values(array_filter($request->request->all('items'), 'is_string'));

        return $itemRepository->findInboxItems($integration, array_slice($itemIds, 0, self::MAX_BULK_ITEMS));
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

    private function redirectToInboxWithNothingSelected(Request $request, Larp $larp): RedirectResponse
    {
        $this->addFlash('warning', $this->translator->trans('inbox.flash.nothing_selected', [], 'discord'));

        return $this->redirectToInbox($request, $larp);
    }

    private function redirectToInbox(Request $request, Larp $larp): RedirectResponse
    {
        parse_str($request->request->getString('returnQuery'), $returnQuery);

        return $this->redirectToRoute('backoffice_larp_discord_inbox', array_merge(
            array_filter($returnQuery, static fn (mixed $value, string|int $key): bool => is_string($key) && $key !== 'larp', ARRAY_FILTER_USE_BOTH),
            ['larp' => $larp->getId()->toRfc4122()],
        ));
    }
}
