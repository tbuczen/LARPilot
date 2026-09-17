<?php

namespace App\Domain\Core\Controller\Backoffice;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Security\Voter\LarpDeleteVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/larp/{larp}/delete', name: 'backoffice_larp_delete', methods: ['POST'])]
#[IsGranted(LarpDeleteVoter::DELETE, subject: 'larp')]
class LarpDeleteController extends AbstractController
{
    public function __invoke(
        Larp $larp,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        // Verify CSRF token (uses generic 'delete' token from unified delete modal)
        if (!$this->isCsrfTokenValid('delete', $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('backoffice_larp_list');
        }

        $larpTitle = $larp->getTitle();

        // Soft delete - Gedmo will handle setting deletedAt
        $entityManager->remove($larp);
        $entityManager->flush();

        $this->addFlash('success', sprintf('LARP "%s" has been deleted.', $larpTitle));

        return $this->redirectToRoute('backoffice_larp_list');
    }
}
