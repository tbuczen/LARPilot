<?php

namespace App\Controller\Backoffice\Larp;

use App\Entity\Larp;
use App\Security\Voter\Backoffice\Larp\LarpDetailsVoter;
use App\Service\Larp\LarpDashboardService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}', name: 'backoffice_larp_dashboard', methods: ['GET'])]
class LarpDashboardController extends AbstractController
{
    public function __invoke(
        Larp $larp,
        LarpDashboardService $dashboardService
    ): Response {
        if (!$this->isGranted(LarpDetailsVoter::VIEW, $larp)) {
            return $this->redirectToRoute('public_larp_list', [], 403);
        }

        $dashboard = $dashboardService->getDashboardData($larp);

        return $this->render('backoffice/larp/dashboard.html.twig', [
            'larp' => $larp,
            'dashboard' => $dashboard,
        ]);
    }
}
