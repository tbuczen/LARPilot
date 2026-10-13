<?php

namespace App\Domain\Integrations\Controller\Backoffice;

use App\Domain\Core\Entity\Larp;
use App\Domain\Integrations\DTO\RemoteSource;
use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Miro\Client\MiroApiException;
use App\Domain\Integrations\Miro\MiroIntegrationProvider;
use App\Domain\Integrations\Miro\Service\MiroBoardSelection;
use App\Domain\Integrations\Security\Voter\IntegrationSettingsVoter;
use App\Domain\Integrations\Service\Exceptions\ReAuthenticationNeededException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/larp/{larp}/integration/miro', name: 'backoffice_larp_integration_miro_')]
class MiroBoardController extends AbstractController
{
    public function __construct(
        private readonly MiroIntegrationProvider $miroIntegrationProvider,
        private readonly MiroBoardSelection $miroBoardSelection,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route('/boards', name: 'boards', methods: ['GET'])]
    public function boards(Larp $larp): Response
    {
        $this->denyAccessUnlessGranted(IntegrationSettingsVoter::MODIFY, $larp);
        $integration = $this->miroIntegration($larp);

        $boards = [];
        $needsReauth = false;
        try {
            $boards = $this->miroIntegrationProvider->listBoards($integration);
        } catch (ReAuthenticationNeededException) {
            $needsReauth = true;
        } catch (MiroApiException) {
            $this->addFlash('danger', $this->translator->trans('boards.load_failed', [], 'miro'));
        }

        return $this->render('domain/integrations/integration_miro/boards.html.twig', [
            'larp' => $larp,
            'integration' => $integration,
            'boards' => $boards,
            'selectedBoardIds' => $this->miroBoardSelection->selectedBoardIds($integration),
            'needsReauth' => $needsReauth,
        ]);
    }

    #[Route('/boards', name: 'board_toggle', methods: ['POST'])]
    public function toggleBoard(Larp $larp, Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted(IntegrationSettingsVoter::MODIFY, $larp);
        $integration = $this->miroIntegration($larp);

        if (!$this->isCsrfTokenValid('miro_board_' . $integration->getId()->toRfc4122(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $boardsRedirect = $this->redirectToRoute('backoffice_larp_integration_miro_boards', ['larp' => $larp->getId()->toRfc4122()]);
        $boardId = $request->request->getString('boardId');
        if ($boardId === '') {
            return $boardsRedirect;
        }

        if (!$request->request->getBoolean('selected')) {
            $this->miroBoardSelection->deselect($integration, $boardId);
            $this->addFlash('success', $this->translator->trans('boards.deselected', [], 'miro'));

            return $boardsRedirect;
        }

        try {
            $board = $this->findBoard($this->miroIntegrationProvider->listBoards($integration), $boardId);
        } catch (ReAuthenticationNeededException) {
            return $boardsRedirect;
        } catch (MiroApiException) {
            $this->addFlash('danger', $this->translator->trans('boards.load_failed', [], 'miro'));

            return $boardsRedirect;
        }

        if (!$board instanceof RemoteSource) {
            throw $this->createNotFoundException('Miro board not found in the connected team.');
        }

        $this->miroBoardSelection->select($integration, $board);
        $this->addFlash('success', $this->translator->trans('boards.selected', ['%board%' => $board->name], 'miro'));

        return $boardsRedirect;
    }

    private function miroIntegration(Larp $larp): LarpIntegration
    {
        $integration = $this->miroIntegrationProvider->findIntegration($larp);
        if (!$integration instanceof LarpIntegration) {
            throw $this->createNotFoundException('Miro is not connected to this LARP.');
        }

        return $integration;
    }

    /**
     * @param list<RemoteSource> $boards
     */
    private function findBoard(array $boards, string $boardId): ?RemoteSource
    {
        foreach ($boards as $board) {
            if ($board->externalId === $boardId) {
                return $board;
            }
        }

        return null;
    }
}
