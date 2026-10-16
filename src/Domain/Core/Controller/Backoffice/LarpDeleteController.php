<?php

namespace App\Domain\Core\Controller\Backoffice;

use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Security\Voter\LarpDeleteVoter;
use App\Domain\Core\Service\LarpDeletionService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/larp/{larp}/delete', name: 'backoffice_larp_delete', methods: ['POST'])]
#[IsGranted(LarpDeleteVoter::DELETE, subject: 'larp')]
class LarpDeleteController extends BaseController
{
    public const CSRF_TOKEN_PREFIX = 'larp_delete_';

    public function __invoke(
        Larp $larp,
        Request $request,
        LarpDeletionService $larpDeletionService,
    ): Response {
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_PREFIX . $larp->getId(), $request->request->getString('_token'))) {
            $this->addFlash('danger', $this->translator->trans('larp.delete.invalid_token'));
            return $this->redirectToRoute('backoffice_larp_status_index', ['larp' => $larp->getId()]);
        }

        $larpTitle = (string) $larp->getTitle();
        if (trim($request->request->getString('confirmTitle')) !== trim($larpTitle)) {
            $this->addFlash('danger', $this->translator->trans('larp.delete.title_mismatch'));
            return $this->redirectToRoute('backoffice_larp_status_index', ['larp' => $larp->getId()]);
        }

        $larpDeletionService->delete($larp);

        $this->addFlash('success', $this->translator->trans('larp.delete.success', ['%title%' => $larpTitle]));

        return $this->redirectToRoute('backoffice_larp_list');
    }
}
