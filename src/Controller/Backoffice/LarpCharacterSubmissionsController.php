<?php

namespace App\Controller\Backoffice;

use App\Controller\BaseController;
use App\Entity\Larp;
use App\Service\Larp\SubmissionStatsService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}/applications', name: 'backoffice_larp_applications_')]
class LarpCharacterSubmissionsController extends BaseController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Larp $larp, SubmissionStatsService $statsService): Response
    {
        $stats = $statsService->getStatsForLarp($larp);

        return $this->render('backoffice/larp/application/list.html.twig', [
            'larp' => $larp,
            'applications' => $stats['applications'],
            'missing' => $stats['missing'],
            'factionStats' => $stats['factionStats'],
        ]);
    }
}
