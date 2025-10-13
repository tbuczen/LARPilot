<?php

namespace App\Domain\Core\Controller\Backoffice;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Form\LarpType;
use App\Domain\Core\UseCase\SubmitLarp\SubmitLarpCommand;
use App\Domain\Core\UseCase\SubmitLarp\SubmitLarpHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/create', name: 'backoffice_larp_create', methods: ['GET', 'POST'])]
class LarpCreateController extends AbstractController
{
    public function __invoke(Request $request, SubmitLarpHandler $handler): Response
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

            $this->addFlash('success', 'Core created as DRAFT.');

            return $this->redirectToRoute('backoffice_larp_dashboard', ['larp' => $dto->larpId]);
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Validation error occurred');
        }

        return $this->render('backoffice/larp/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
