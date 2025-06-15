<?php

namespace App\Controller\Backoffice;

use App\Repository\LarpRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp', name: 'backoffice_larp_')]

class LarpIncidentsController extends AbstractController
{
    #[Route('/{id}/incidents', name: 'incidents', methods: ['GET'])]
    public function incidents(string $id, LarpRepository $larpRepository): Response
    {
        $larp = $larpRepository->find($id);
        if (!$larp) {
            throw $this->createNotFoundException('Larp not found.');
        }

        return $this->render('backoffice/larp/incidents.html.twig', [
            'larp' => $larp,
        ]);
    }
}
