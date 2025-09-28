<?php

namespace App\Controller\Backoffice\Larp;

use App\Entity\User;
use App\Repository\LarpRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp', name: 'backoffice_larp_list', methods: ['GET'])]
class LarpListController extends AbstractController
{
    public function __invoke(LarpRepository $larpRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $larps = $larpRepository->findAllWhereParticipating($user);
        return $this->render('backoffice/larp/list.html.twig', [
            'larps' => $larps,
        ]);
    }
}