<?php

namespace App\Domain\Integrations\Controller\Backoffice;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Entity\Larp;
use App\Domain\Integrations\Entity\ExternalSource;
use App\Domain\Integrations\Miro\Client\MiroApiException;
use App\Domain\Integrations\Miro\Import\MiroImportApplier;
use App\Domain\Integrations\Miro\Import\MiroImportPlan;
use App\Domain\Integrations\Miro\Import\MiroImportPlanner;
use App\Domain\Integrations\Miro\Snapshot\MiroBoardSnapshotProvider;
use App\Domain\Integrations\Repository\ExternalSourceRepository;
use App\Domain\Integrations\Security\Voter\StorySourceVoter;
use App\Domain\Integrations\Service\Exceptions\ReAuthenticationNeededException;
use App\Domain\Integrations\Service\Exceptions\SourceSyncForbiddenException;
use App\Domain\Integrations\Service\Exceptions\SourceSyncRateLimitedException;
use App\Domain\Integrations\Service\Exceptions\SourceSyncUnauthorizedException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}/integration/miro/boards/{source}/import', name: 'backoffice_larp_integration_miro_import_')]
class MiroBoardImportController extends BaseController
{
    public const IMPORT_CSRF_TOKEN = 'miro_board_import';

    #[Route('', name: 'preview', methods: ['GET'])]
    public function preview(
        Larp $larp,
        string $source,
        ExternalSourceRepository $sourceRepository,
        MiroBoardSnapshotProvider $snapshotProvider,
        MiroImportPlanner $importPlanner,
    ): Response {
        $this->denyAccessUnlessGranted(StorySourceVoter::BROWSE, $larp);
        $board = $this->findBoard($source, $larp, $sourceRepository);

        $problem = null;
        $retryAfterSeconds = null;
        $plan = null;
        try {
            $plan = $importPlanner->plan($board, $snapshotProvider->snapshot($board->getIntegration(), $board->getExternalId()));
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

        return $this->render('domain/integrations/integration_miro/import.html.twig', [
            'larp' => $larp,
            'board' => $board,
            'plan' => $plan,
            'problem' => $problem,
            'retryAfterSeconds' => $retryAfterSeconds,
        ]);
    }

    #[Route('', name: 'apply', methods: ['POST'])]
    public function apply(
        Request $request,
        Larp $larp,
        string $source,
        ExternalSourceRepository $sourceRepository,
        MiroBoardSnapshotProvider $snapshotProvider,
        MiroImportPlanner $importPlanner,
        MiroImportApplier $importApplier,
    ): RedirectResponse {
        $this->denyAccessUnlessGranted(StorySourceVoter::BROWSE, $larp);
        $board = $this->findBoard($source, $larp, $sourceRepository);
        if (!$this->isCsrfTokenValid(self::IMPORT_CSRF_TOKEN . '_' . $board->getId()->toRfc4122(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $confirmedTokens = array_values(array_filter(
            $request->request->all('rows'),
            static fn (mixed $token): bool => is_string($token) && $token !== '',
        ));
        if ($confirmedTokens === []) {
            $this->addFlash('warning', $this->translator->trans('import.nothing_selected', [], 'miro'));

            return $this->redirectToPreview($larp, $board);
        }

        $plan = $this->currentPlan($board, $snapshotProvider, $importPlanner);
        if (!$plan instanceof MiroImportPlan) {
            $this->addFlash('error', $this->translator->trans('browser.problem.unavailable', [], 'miro'));

            return $this->redirectToPreview($larp, $board);
        }

        $result = $importApplier->apply($board, $plan, $confirmedTokens, $user);
        if ($result->isEmpty()) {
            $this->addFlash('warning', $result->skippedRows > 0
                ? $this->translator->trans('import.skipped', ['%count%' => $result->skippedRows], 'miro')
                : $this->translator->trans('import.outdated', [], 'miro'));

            return $this->redirectToPreview($larp, $board);
        }

        $this->addFlash('success', $this->translator->trans('import.applied', [
            '%created%' => $result->createdObjects,
            '%linked%' => $result->linkedItems,
            '%relations%' => $result->createdRelations,
        ], 'miro'));
        if ($result->skippedRows > 0) {
            $this->addFlash('warning', $this->translator->trans('import.skipped', ['%count%' => $result->skippedRows], 'miro'));
        }

        return $this->redirectToPreview($larp, $board);
    }

    private function currentPlan(ExternalSource $board, MiroBoardSnapshotProvider $snapshotProvider, MiroImportPlanner $importPlanner): ?MiroImportPlan
    {
        try {
            return $importPlanner->plan($board, $snapshotProvider->snapshot($board->getIntegration(), $board->getExternalId()));
        } catch (SourceSyncRateLimitedException|SourceSyncUnauthorizedException|SourceSyncForbiddenException|ReAuthenticationNeededException|MiroApiException) {
            return null;
        }
    }

    private function findBoard(string $sourceId, Larp $larp, ExternalSourceRepository $sourceRepository): ExternalSource
    {
        return $sourceRepository->findMiroBoardOfLarp($sourceId, $larp) ?? throw $this->createNotFoundException();
    }

    private function redirectToPreview(Larp $larp, ExternalSource $board): RedirectResponse
    {
        return $this->redirectToRoute('backoffice_larp_integration_miro_import_preview', [
            'larp' => $larp->getId()->toRfc4122(),
            'source' => $board->getId()->toRfc4122(),
        ]);
    }
}
