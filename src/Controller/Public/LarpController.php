<?php

namespace App\Controller\Public;

use App\Repository\LarpRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/', name: 'public_larp_')]
class LarpController extends AbstractController
{
    #[Route('/', name: 'list', methods: ['GET'])]
    public function list(LarpRepository $larpRepository): Response
    {
        $larps = $larpRepository->findAll();
        return $this->render('public/larp/index.html.twig', [
            'larps' => $larps,
        ]);
    }
}
