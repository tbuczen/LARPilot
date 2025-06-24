<?php

namespace App\Controller\Backoffice;

use App\Controller\BaseController;
use App\Entity\Enum\LarpIncidentStatus;
use App\Form\Filter\LarpIncidentFilterType;
use App\Repository\LarpIncidentRepository;
use App\Repository\LarpRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp', name: 'backoffice_larp_')]

class LarpIncidentsController extends BaseController
{
    #[Route('/{id}/incidents', name: 'incidents', methods: ['GET', 'POST'])]
    public function incidents(
        Request                $request,
        string                 $id,
        LarpRepository         $larpRepository,
        LarpIncidentRepository $incidentRepository,
    ): Response {
        $larp = $larpRepository->find($id);
        if (!$larp) {
            throw $this->createNotFoundException('Larp not found.');
        }

        $filterForm = $this->createForm(LarpIncidentFilterType::class);
        $filterForm->handleRequest($request);
        $criteria = ['larp' => $larp];
        $data = $filterForm->getData() ?? [];

        if (!empty($data['status'])) {
            $criteria['status'] = $data['status'];
        }

        if (!empty($data['caseId'])) {
            $criteria['caseId'] = $data['caseId'];
        }

        $incidents = $incidentRepository->findBy($criteria);

        return $this->render('backoffice/larp/incidents.html.twig', [
            'larp' => $larp,
            'incidents' => $incidents,
            'filterForm' => $filterForm->createView(),
        ]);
    }
}
