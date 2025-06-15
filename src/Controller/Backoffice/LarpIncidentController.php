<?php

namespace App\Controller\Backoffice;

use App\Entity\Larp;
use App\Entity\LarpIncident;
use App\Repository\LarpIncidentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}/incident', name: 'backoffice_larp_incident_')]
class LarpIncidentController extends AbstractController
{
    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(Larp $larp, LarpIncidentRepository $repository): Response
    {
        $incidents = $repository->findBy(['larp' => $larp]);

        return $this->render('backoffice/larp/incidents.html.twig', [
            'larp' => $larp,
            'incidents' => $incidents,
        ]);
    }

    #[Route('/{incident}', name: 'view', methods: ['GET'])]
    public function view(Larp $larp, LarpIncident $incident): Response
    {
        return $this->render('backoffice/larp/incident/view.html.twig', [
            'larp' => $larp,
            'incident' => $incident,
        ]);
    }
}
