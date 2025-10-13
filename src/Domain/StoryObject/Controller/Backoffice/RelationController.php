<?php

namespace App\Domain\StoryObject\Controller\Backoffice;

use App\Domain\Core\Entity\Larp;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp', name: 'backoffice_larp_story_')]
class RelationController extends AbstractController
{
    #[Route('/{larp}/story/relations', name: 'relations', methods: ['GET'])]
    public function relations(Larp $larp): Response
    {
        return $this->render('backoffice/larp/details.html.twig', [
            'larp' => $larp,
        ]);
    }
}
