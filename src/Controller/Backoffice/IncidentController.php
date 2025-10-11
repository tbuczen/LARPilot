<?php

namespace App\Controller\Backoffice;

use App\Controller\BaseController;
use App\Entity\Larp;
use App\Entity\LarpIncident;
use App\Form\Filter\LarpIncidentFilterType;
use App\Repository\LarpIncidentRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}', name: 'backoffice_larp_')]

class IncidentController extends BaseController
{
    #[Route('/incidents', name: 'incidents', methods: ['GET', 'POST'])]
    public function incidents(
        Request                $request,
        Larp                 $larp,
        LarpIncidentRepository $incidentRepository,
    ): Response {
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

    #[Route('/incident/{incident}', name: 'incident_view', methods: ['GET'])]
    public function view(Larp $larp, LarpIncident $incident): Response
    {
        return $this->render('backoffice/larp/incident/view.html.twig', [
            'larp' => $larp,
            'incident' => $incident,
        ]);
    }
}
