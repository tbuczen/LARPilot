<?php

namespace App\Controller\Backoffice\Story;

use App\Entity\Larp;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp', name: 'backoffice_larp_story_')]

class LarpQuestsController extends AbstractController
{
    #[Route('/{larp}/story/quests', name: 'quests', methods: ['GET', 'POST'])]
    public function quests(Larp $larp): Response
    {
        return $this->render('backoffice/larp/details.html.twig', [
            'larp' => $larp,
        ]);
    }

    #[Route('/{larp}/story/events', name: 'events', methods: ['GET', 'POST'])]
    public function events(Larp $larp): Response
    {
        return $this->render('backoffice/larp/details.html.twig', [
            'larp' => $larp,
        ]);
    }
}