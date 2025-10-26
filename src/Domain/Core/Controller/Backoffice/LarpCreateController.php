<?php

namespace App\Domain\Core\Controller\Backoffice;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Enum\LarpStageStatus;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Form\LarpType;
use App\Domain\Core\Service\LarpManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/create', name: 'backoffice_larp_create', methods: ['GET', 'POST'])]
class LarpCreateController extends AbstractController
{
    public function __invoke(Request $request, LarpManager $larpManager): Response
    {
        $larp = new Larp();
        $form = $this->createForm(LarpType::class, $larp);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Larp $larp */
            $larp = $form->getData();
            $larp->setStatus(LarpStageStatus::DRAFT);

            /** @var User $user */
            $user = $this->getUser();
            $larp = $larpManager->createLarp($larp, $user);

            $this->addFlash('success', 'Core created as DRAFT.');

            return $this->redirectToRoute('backoffice_larp_dashboard', ['larp' => $larp->getId()->toRfc4122()]);
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Validation error occurred');
        }

        return $this->render('backoffice/larp/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
