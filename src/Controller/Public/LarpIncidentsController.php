<?php

namespace App\Controller\Public;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/incident', name: 'incident_')]
class LarpIncidentsController extends AbstractController
{
    #[Route('/create', name: 'create', methods: ['GET', 'POST'])]
    public function create(): Response
    {
        return $this->render('public/incidents/create.html.twig');
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(): Response
    {
        return $this->render('public/incidents/list.html.twig');
    }

    #[Route('/{id}', name: 'view', methods: ['GET'])]
    public function view(): Response
    {
        return $this->render('public/incidents/details.html.twig');
    }
}
