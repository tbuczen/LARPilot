<?php

namespace App\Controller\Backoffice;

use App\Domain\Larp\UseCase\SubmitLarp\SubmitLarpCommand;
use App\Domain\Larp\UseCase\SubmitLarp\SubmitLarpHandler;
use App\Entity\Larp;
use App\Form\LarpType;
use App\Repository\LarpRepository;
use App\Security\Voter\Backoffice\Larp\LarpDetailsVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp', name: 'backoffice_larp_')]
class LarpController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function index(LarpRepository $larpRepository): Response
    {
        $larps = $larpRepository->findAll();
        return $this->render('backoffice/larp/list.html.twig', [
            'larps' => $larps,
        ]);
    }

    #[Route('/create', name: 'create', methods: ['GET', 'POST'])]
    public function submit(Request $request, SubmitLarpHandler $handler): Response
    {
        $larp = new Larp();
        $form = $this->createForm(LarpType::class, $larp);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $command = new SubmitLarpCommand(
                name: $data->getName(),
                description: $data->getDescription(),
                submittedByUserId: $this->getUser()->getId()->toRfc4122(),
                location: $data->getLocation(),
                startDate: $data->getStartDate(),
                endDate: $data->getEndDate()
            );

            $dto = $handler->handle($command);

            $this->addFlash('success', 'Larp created as DRAFT.');

            return $this->redirectToRoute('backoffice_larp_details', ['id' => $dto->larpId]);
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Validation error occurred');

        }

        return $this->render('backoffice/larp/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'details', methods: ['GET'])]
    public function details(string $id, LarpRepository $larpRepository): Response
    {
        $larp = $larpRepository->find($id);
        if (!$this->isGranted(LarpDetailsVoter::VIEW, $larp)) {
            return $this->redirectToRoute('public_larp_list', [], 403);
        }

        return $this->render('backoffice/larp/details.html.twig', [
            'larp' => $larp,
        ]);
    }
}