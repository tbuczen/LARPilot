<?php

namespace App\Controller\Backoffice\Story;

use App\Repository\LarpRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp', name: 'backoffice_larp_story_')]

class LarpCharactersController extends AbstractController
{
    #[Route('/{id}/story/characters', name: 'characters', methods: ['GET', 'POST'])]
    public function characters(string $id, LarpRepository $larpRepository): Response
    {
        $larp = $larpRepository->find($id);
        if (!$larp) {
            throw $this->createNotFoundException('Larp not found.');
        }

        return $this->render('backoffice/larp/details.html.twig', [
            'larp' => $larp,
        ]);
    }
}