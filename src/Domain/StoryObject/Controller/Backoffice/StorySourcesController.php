<?php

namespace App\Domain\StoryObject\Controller\Backoffice;

use App\Domain\Account\Entity\User;
use App\Domain\Application\Repository\LarpApplicationChoiceRepository;
use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Entity\Larp;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\Enum\ReferenceRole;
use App\Domain\Integrations\Entity\ExternalReference;
use App\Domain\Integrations\Repository\ExternalItemRepository;
use App\Domain\Integrations\Repository\ExternalReferenceRepository;
use App\Domain\Integrations\Security\Voter\StorySourceVoter;
use App\Domain\Integrations\Service\ExternalReferenceLinker;
use App\Domain\Integrations\Service\StorySourceLinkService;
use App\Domain\StoryObject\Entity\Character;
use App\Domain\StoryObject\Entity\Enum\TargetType;
use App\Domain\StoryObject\Entity\StoryObject;
use App\Domain\StoryObject\Repository\CommentRepository;
use App\Domain\StoryObject\Service\StoryObjectMentionService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}/story/{storyObject}/sources', name: 'backoffice_larp_story_sources_')]
class StorySourcesController extends BaseController
{
    public const LINK_CSRF_TOKEN = 'story_source_link';

    public const UNLINK_CSRF_TOKEN = 'delete';

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(
        Larp $larp,
        StoryObject $storyObject,
        ExternalReferenceRepository $referenceRepository,
        CommentRepository $commentRepository,
        StoryObjectMentionService $mentionService,
        LarpApplicationChoiceRepository $choiceRepository,
    ): Response {
        $this->assertStoryObjectBelongsToLarp($storyObject, $larp);

        $references = $referenceRepository->findForStoryObject($storyObject);
        $isLoreDocument = $storyObject->getTargetType() === TargetType::LoreDocument;

        return $this->render('domain/story_object/story_sources/list.html.twig', [
            'larp' => $larp,
            'storyObject' => $storyObject,
            'storyObjectType' => $storyObject->getTargetType()->value,
            'referenceGroups' => $this->groupByProvider($references),
            'referencesCount' => count($references),
            'canLink' => $this->isGranted(StorySourceVoter::LINK, $storyObject),
            'roles' => ReferenceRole::cases(),
            'mentionsCount' => $isLoreDocument ? null : count($mentionService->findMentions($storyObject)),
            'applicantsCount' => $storyObject instanceof Character ? $choiceRepository->getApplicationsCountForCharacter($storyObject) : 0,
            'commentsCount' => $commentRepository->countByStoryObject($storyObject),
            'unresolvedCommentsCount' => $commentRepository->countUnresolvedByStoryObject($storyObject),
        ]);
    }

    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(
        Request $request,
        Larp $larp,
        StoryObject $storyObject,
        ExternalItemRepository $itemRepository,
    ): JsonResponse {
        $this->assertStoryObjectBelongsToLarp($storyObject, $larp);
        $this->denyAccessUnlessGranted(StorySourceVoter::LINK, $storyObject);

        $items = $itemRepository->searchForLarp($larp, $request->query->getString('q'));

        $results = [];
        foreach ($items as $item) {
            $results[] = [
                'id' => $item->getId()->toRfc4122(),
                'title' => $item->getTitle() ?? $item->getExternalId(),
                'source' => $item->getSource()->getTitle(),
                'provider' => $item->getSource()->getIntegration()->getProvider()->getLabel(),
                'kind' => $this->translator->trans('story_source.kind.' . $item->getKind()->referenceType()->value),
            ];
        }

        return new JsonResponse(['items' => $results]);
    }

    #[Route('/link', name: 'link', methods: ['POST'])]
    public function link(
        Request $request,
        Larp $larp,
        StoryObject $storyObject,
        StorySourceLinkService $linkService,
    ): RedirectResponse {
        $this->assertStoryObjectBelongsToLarp($storyObject, $larp);
        $this->denyAccessUnlessGranted(StorySourceVoter::LINK, $storyObject);

        if (!$this->isCsrfTokenValid(self::LINK_CSRF_TOKEN, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $role = ReferenceRole::tryFrom($request->request->getString('role')) ?? ReferenceRole::Mention;
        $itemId = $request->request->getString('item');
        $url = $request->request->getString('url');

        $reference = match (true) {
            $itemId !== '' => $linkService->linkCachedItem($storyObject, $itemId, $role, $user),
            trim($url) !== '' => $linkService->linkUrl($storyObject, $url, $role, $user),
            default => null,
        };

        if ($reference instanceof ExternalReference) {
            $this->addFlash('success', $this->translator->trans('story_source.linked'));
        } else {
            $this->addFlash('error', $this->translator->trans($itemId !== '' ? 'story_source.item_not_found' : 'story_source.invalid_url'));
        }

        return $this->redirectToSources($larp, $storyObject);
    }

    #[Route('/{reference}/unlink', name: 'unlink', methods: ['POST'])]
    public function unlink(
        Request $request,
        Larp $larp,
        StoryObject $storyObject,
        ExternalReference $reference,
        ExternalReferenceLinker $linker,
    ): RedirectResponse {
        $this->assertStoryObjectBelongsToLarp($storyObject, $larp);
        $this->denyAccessUnlessGranted(StorySourceVoter::LINK, $storyObject);

        if ($reference->getStoryObject()?->getId()->equals($storyObject->getId()) !== true) {
            throw $this->createNotFoundException();
        }

        if (!$this->isCsrfTokenValid(self::UNLINK_CSRF_TOKEN, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $linker->unlink($storyObject, $reference->getProvider(), $reference->getExternalId());
        $this->addFlash('success', $this->translator->trans('story_source.unlinked'));

        return $this->redirectToSources($larp, $storyObject);
    }

    /**
     * @param ExternalReference[] $references
     * @return array<int, array{provider: LarpIntegrationProvider, references: ExternalReference[]}>
     */
    private function groupByProvider(array $references): array
    {
        $groups = [];
        foreach ($references as $reference) {
            $providerKey = $reference->getProvider()->value;
            $groups[$providerKey] ??= ['provider' => $reference->getProvider(), 'references' => []];
            $groups[$providerKey]['references'][] = $reference;
        }

        return array_values($groups);
    }

    private function assertStoryObjectBelongsToLarp(StoryObject $storyObject, Larp $larp): void
    {
        if ($storyObject->getLarp()?->getId()->equals($larp->getId()) === true) {
            return;
        }

        throw $this->createNotFoundException();
    }

    private function redirectToSources(Larp $larp, StoryObject $storyObject): RedirectResponse
    {
        return $this->redirectToRoute('backoffice_larp_story_sources_list', [
            'larp' => $larp->getId(),
            'storyObject' => $storyObject->getId(),
        ]);
    }
}
