<?php

declare(strict_types=1);

namespace App\Domain\Application\Controller\Backoffice;

use App\Domain\Application\Entity\LarpApplicationTurn;
use App\Domain\Application\Form\LarpApplicationTurnType;
use App\Domain\Application\Repository\LarpApplicationTurnRepository;
use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Entity\Larp;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}/applications/turns', name: 'backoffice_larp_application_turns_')]
class LarpApplicationTurnController extends BaseController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Larp $larp, LarpApplicationTurnRepository $repository): Response
    {
        return $this->render('backoffice/larp/application/turns/list.html.twig', [
            'larp' => $larp,
            'turns' => $repository->findByLarpOrdered($larp),
        ]);
    }

    #[Route('/create', name: 'create', methods: ['GET', 'POST'])]
    public function create(Request $request, Larp $larp, LarpApplicationTurnRepository $repository, EntityManagerInterface $em): Response
    {
        $turn = new LarpApplicationTurn();
        $turn->setLarp($larp);
        $turn->setRoundNumber($repository->findNextRoundNumber($larp));

        $form = $this->createForm(LarpApplicationTurnType::class, $turn);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($turn);
            $em->flush();

            $this->addFlash('success', $this->translator->trans('flash.saved'));

            return $this->redirectToRoute('backoffice_larp_application_turns_list', ['larp' => $larp->getId()]);
        }

        return $this->render('backoffice/larp/application/turns/modify.html.twig', [
            'larp' => $larp,
            'turn' => $turn,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{turn}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Larp $larp, LarpApplicationTurn $turn, EntityManagerInterface $em): Response
    {
        if ($turn->getLarp() !== $larp) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(LarpApplicationTurnType::class, $turn);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', $this->translator->trans('flash.saved'));

            return $this->redirectToRoute('backoffice_larp_application_turns_list', ['larp' => $larp->getId()]);
        }

        return $this->render('backoffice/larp/application/turns/modify.html.twig', [
            'larp' => $larp,
            'turn' => $turn,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{turn}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Larp $larp, LarpApplicationTurn $turn, EntityManagerInterface $em): Response
    {
        if ($turn->getLarp() !== $larp) {
            throw $this->createNotFoundException();
        }

        if ($this->isCsrfTokenValid('delete_turn_' . $turn->getId(), (string) $request->request->get('_token'))) {
            $em->remove($turn);
            $em->flush();

            $this->addFlash('success', $this->translator->trans('flash.deleted'));
        }

        return $this->redirectToRoute('backoffice_larp_application_turns_list', ['larp' => $larp->getId()]);
    }
}
