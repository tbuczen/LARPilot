<?php

namespace App\Controller\Backoffice;

use App\Repository\LarpRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/larps', name: 'backoffice_larp_')]
class LarpController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(LarpRepository $larpRepository): Response
    {
        $larps = $larpRepository->findAll();
        return $this->render('backoffice/larp/index.html.twig', [
            'larps' => $larps,
        ]);
    }
}