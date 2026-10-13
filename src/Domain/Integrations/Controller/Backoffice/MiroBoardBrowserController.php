<?php

namespace App\Domain\Integrations\Controller\Backoffice;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Entity\Larp;
use App\Domain\Integrations\DTO\ExternalReferenceLink;
use App\Domain\Integrations\Entity\Enum\ExternalSourceKind;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\Enum\ReferenceRole;
use App\Domain\Integrations\Entity\Enum\ReferenceType;
use App\Domain\Integrations\Entity\ExternalReference;
use App\Domain\Integrations\Entity\ExternalSource;
use App\Domain\Integrations\Miro\Browser\MiroBoardBrowser;
use App\Domain\Integrations\Miro\Client\MiroApiException;
use App\Domain\Integrations\Miro\Snapshot\MiroBoardEntry;
use App\Domain\Integrations\Miro\Snapshot\MiroBoardItemRecorder;
use App\Domain\Integrations\Miro\Snapshot\MiroBoardSnapshotProvider;
use App\Domain\Integrations\Miro\Snapshot\MiroBoardUrls;
use App\Domain\Integrations\Repository\ExternalReferenceRepository;
use App\Domain\Integrations\Repository\ExternalSourceRepository;
use App\Domain\Integrations\Security\Voter\IntegrationSettingsVoter;
use App\Domain\Integrations\Security\Voter\StorySourceVoter;
use App\Domain\Integrations\Service\Exceptions\ReAuthenticationNeededException;
use App\Domain\Integrations\Service\Exceptions\SourceSyncForbiddenException;
use App\Domain\Integrations\Service\Exceptions\SourceSyncRateLimitedException;
use App\Domain\Integrations\Service\Exceptions\SourceSyncUnauthorizedException;
use App\Domain\Integrations\Service\ExternalReferenceLinker;
use App\Domain\Integrations\Service\Sync\SyncNowDispatcher;
use App\Domain\StoryObject\Entity\StoryObject;
use App\Domain\StoryObject\Repository\StoryObjectRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/larp/{larp}/integration/miro/boards/{source}', name: 'backoffice_larp_integration_miro_browser_')]
class MiroBoardBrowserController extends BaseController
{
    public const LINK_CSRF_TOKEN = 'miro_board_link';

    public const UNLINK_CSRF_TOKEN = 'miro_board_unlink';

    public const REFRESH_CSRF_TOKEN = 'miro_board_refresh';

    #[Route('', name: 'show', methods: ['GET'])]
    public function show(
        Larp $larp,
        string $source,
        ExternalSourceRepository $sourceRepository,
        MiroBoardSnapshotProvider $snapshotProvider,
        MiroBoardBrowser $boardBrowser,
    ): Response {
        $this->denyAccessUnlessGranted(StorySourceVoter::BROWSE, $larp);
        $board = $this->findBoard($source, $larp, $sourceRepository);

        $problem = null;
        $retryAfterSeconds = null;
        $boardView = null;
        try {
            $boardView = $boardBrowser->view($board, $snapshotProvider->snapshot($board->getIntegration(), $board->getExternalId()));
        } catch (SourceSyncRateLimitedException $rateLimited) {
            $problem = 'rate_limited';
            $retryAfterSeconds = $rateLimited->getRetryAfterSeconds();
        } catch (SourceSyncUnauthorizedException|ReAuthenticationNeededException $unauthorized) {
            $problem = 'needs_reauth';
            $board->getIntegration()->markNeedsReauth($unauthorized->getMessage());
            $this->entityManager->flush();
        } catch (SourceSyncForbiddenException) {
            $problem = 'forbidden';
        } catch (MiroApiException) {
            $problem = 'unavailable';
        }

        return $this->render('domain/integrations/integration_miro/browser.html.twig', [
            'larp' => $larp,
            'board' => $board,
            'boardView' => $boardView,
            'problem' => $problem,
            'retryAfterSeconds' => $retryAfterSeconds,
            'roles' => ReferenceRole::cases(),
            'boardUrl' => MiroBoardUrls::board($board->getExternalId()),
            'liveEmbedUrl' => MiroBoardUrls::liveEmbed($board->getExternalId()),
            'canManageIntegration' => $this->isGranted(IntegrationSettingsVoter::MODIFY, $larp),
        ]);
    }

    #[Route('/refresh', name: 'refresh', methods: ['POST'])]
    public function refresh(
        Request $request,
        Larp $larp,
        string $source,
        ExternalSourceRepository $sourceRepository,
        MiroBoardSnapshotProvider $snapshotProvider,
        SyncNowDispatcher $syncNowDispatcher,
    ): RedirectResponse {
        $this->denyAccessUnlessGranted(StorySourceVoter::BROWSE, $larp);
        $board = $this->findBoard($source, $larp, $sourceRepository);
        $this->assertCsrfToken(self::REFRESH_CSRF_TOKEN . '_' . $board->getId()->toRfc4122(), $request);

        $snapshotProvider->forget($board->getIntegration(), $board->getExternalId());
        $syncRequested = $syncNowDispatcher->dispatch($board);

        $this->addFlash('success', $this->translator->trans($syncRequested ? 'browser.refresh_requested' : 'browser.refreshed', [], 'miro'));

        return $this->redirectToBoard($larp, $board);
    }

    #[Route('/story-objects', name: 'story_object_search', methods: ['GET'])]
    public function searchStoryObjects(
        Request $request,
        Larp $larp,
        string $source,
        ExternalSourceRepository $sourceRepository,
        StoryObjectRepository $storyObjectRepository,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(StorySourceVoter::BROWSE, $larp);
        $this->findBoard($source, $larp, $sourceRepository);

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

    #[Route('/link', name: 'link', methods: ['POST'])]
    public function link(
        Request $request,
        Larp $larp,
        string $source,
        ExternalSourceRepository $sourceRepository,
        StoryObjectRepository $storyObjectRepository,
        ExternalReferenceRepository $referenceRepository,
        MiroBoardSnapshotProvider $snapshotProvider,
        MiroBoardItemRecorder $itemRecorder,
        ExternalReferenceLinker $linker,
    ): RedirectResponse {
        $this->denyAccessUnlessGranted(StorySourceVoter::BROWSE, $larp);
        $board = $this->findBoard($source, $larp, $sourceRepository);
        $this->assertCsrfToken(self::LINK_CSRF_TOKEN . '_' . $board->getId()->toRfc4122(), $request);

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $storyObject = $this->findStoryObjectOfLarp($request->request->getString('storyObject'), $larp, $storyObjectRepository);
        $this->denyAccessUnlessGranted(StorySourceVoter::LINK, $storyObject);

        $targetId = $request->request->getString('target');
        $role = ReferenceRole::tryFrom($request->request->getString('role')) ?? ReferenceRole::Mention;

        if ($referenceRepository->findOneForStoryObject($storyObject, LarpIntegrationProvider::Miro, $targetId) instanceof ExternalReference) {
            $this->addFlash('warning', $this->translator->trans('browser.already_linked', ['%storyObject%' => $storyObject->getTitle()], 'miro'));

            return $this->redirectToBoard($larp, $board);
        }

        if ($targetId === $board->getExternalId()) {
            $reference = $linker->link($storyObject, new ExternalReferenceLink(
                provider: LarpIntegrationProvider::Miro,
                externalId: $board->getExternalId(),
                referenceType: ReferenceType::Board,
                role: $role,
                name: $board->getTitle(),
                url: MiroBoardUrls::board($board->getExternalId()),
                linkedBy: $user,
            ));
            $reference->setSource($board);
            $this->entityManager->flush();
            $this->addFlash('success', $this->translator->trans('browser.linked', ['%storyObject%' => $storyObject->getTitle()], 'miro'));

            return $this->redirectToBoard($larp, $board);
        }

        $entry = $this->findEntry($board, $targetId, $snapshotProvider);
        if (!$entry instanceof MiroBoardEntry) {
            $this->addFlash('error', $this->translator->trans('browser.item_not_found', [], 'miro'));

            return $this->redirectToBoard($larp, $board);
        }

        $linker->linkItem($storyObject, $itemRecorder->record($board, $entry), $role, $user);
        $this->addFlash('success', $this->translator->trans('browser.linked', ['%storyObject%' => $storyObject->getTitle()], 'miro'));

        return $this->redirectToBoard($larp, $board);
    }

    #[Route('/references/{reference}/unlink', name: 'unlink', methods: ['POST'])]
    public function unlink(
        Request $request,
        Larp $larp,
        string $source,
        ExternalReference $reference,
        ExternalSourceRepository $sourceRepository,
        ExternalReferenceLinker $linker,
    ): RedirectResponse {
        $this->denyAccessUnlessGranted(StorySourceVoter::BROWSE, $larp);
        $board = $this->findBoard($source, $larp, $sourceRepository);

        $storyObject = $reference->getStoryObject();
        if (!$storyObject instanceof StoryObject || $reference->getProvider() !== LarpIntegrationProvider::Miro || !$reference->getLarp()->getId()->equals($larp->getId())) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted(StorySourceVoter::LINK, $storyObject);
        $this->assertCsrfToken(self::UNLINK_CSRF_TOKEN . '_' . $reference->getId()->toRfc4122(), $request);

        $linker->unlink($storyObject, $reference->getProvider(), $reference->getExternalId());
        $this->addFlash('success', $this->translator->trans('browser.unlinked', ['%storyObject%' => $storyObject->getTitle()], 'miro'));

        return $this->redirectToBoard($larp, $board);
    }

    private function findEntry(ExternalSource $board, string $targetId, MiroBoardSnapshotProvider $snapshotProvider): ?MiroBoardEntry
    {
        if ($targetId === '') {
            return null;
        }

        try {
            return $snapshotProvider->snapshot($board->getIntegration(), $board->getExternalId())->entry($targetId);
        } catch (SourceSyncRateLimitedException|SourceSyncUnauthorizedException|SourceSyncForbiddenException|ReAuthenticationNeededException|MiroApiException) {
            return null;
        }
    }

    private function findBoard(string $sourceId, Larp $larp, ExternalSourceRepository $sourceRepository): ExternalSource
    {
        if (!Uuid::isValid($sourceId)) {
            throw $this->createNotFoundException();
        }

        $board = $sourceRepository->find($sourceId);
        if (!$board instanceof ExternalSource || $board->getKind() !== ExternalSourceKind::MiroBoard) {
            throw $this->createNotFoundException();
        }

        $integration = $board->getIntegration();
        if ($integration->getProvider() !== LarpIntegrationProvider::Miro || !$integration->getLarp()->getId()->equals($larp->getId())) {
            throw $this->createNotFoundException();
        }

        return $board;
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

    private function redirectToBoard(Larp $larp, ExternalSource $board): RedirectResponse
    {
        return $this->redirectToRoute('backoffice_larp_integration_miro_browser_show', [
            'larp' => $larp->getId()->toRfc4122(),
            'source' => $board->getId()->toRfc4122(),
        ]);
    }
}
