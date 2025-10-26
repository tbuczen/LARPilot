<?php

namespace App\Domain\Core\Controller\Backoffice;

use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\LarpParticipant;
use App\Domain\Core\Form\Filter\ParticipantFilterType;
use App\Domain\Core\Form\LarpParticipantType;
use App\Domain\Core\Repository\LarpParticipantRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}', name: 'backoffice_larp_participant_')]
class ParticipantController extends BaseController
{
    #[Route('/participants', name: 'list', methods: ['GET', 'POST'])]
    public function list(Request $request, Larp $larp, LarpParticipantRepository $repository): Response
    {
        $filterForm = $this->createForm(ParticipantFilterType::class, null, ['larp' => $larp]);
        $filterForm->handleRequest($request);
        $qb = $repository->createQueryBuilder('c');
        $this->filterBuilderUpdater->addFilterConditions($filterForm, $qb);
        $pagination = $this->getPagination($qb, $request);

        $this->entityPreloader->preload($pagination->getItems(), 'user');
        $this->entityPreloader->preload($pagination->getItems(), 'larpCharacters');

        return $this->render('backoffice/larp/participant/list.html.twig', [
            'larp' => $larp,
            'participants' => $pagination,
            'filterForm' => $filterForm->createView(),
        ]);
    }

    #[Route('/participant/{participant}', name: 'modify', defaults: ['participant' => null], methods: ['GET', 'POST'])]
    public function modify(
        Request                  $request,
        Larp                     $larp,
        LarpParticipantRepository $participantRepository,
        ?LarpParticipant          $participant = null,
    ): Response {
        $form = $this->createForm(LarpParticipantType::class, $participant, ['larp' => $larp]);
        $form->handleRequest($request);

        if (!$participant instanceof LarpParticipant) {
            $participant = new LarpParticipant();
        } else {
            $this->entityPreloader->preload([$participant], 'larpCharacters');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $participant->setLarp($larp);
            $participantRepository->save($participant);
            $this->addFlash('success', $this->translator->trans('success_save'));
            return $this->redirectToRoute('backoffice_larp_participant_list', ['larp' => $larp->getId()]);
        }

        return $this->render('backoffice/larp/participant/modify.html.twig', [
            'form' => $form->createView(),
            'larp' => $larp,
            'participant' => $participant,
        ]);
    }

    #[Route('/participant/{participant}/delete', name: 'delete', methods: ['GET', 'POST'])]
    public function delete(
        Larp                     $larp,
        LarpParticipantRepository $participantRepository,
        LarpParticipant           $participant,
    ): Response {
        $participantRepository->remove($participant);
        $this->addFlash('success', $this->translator->trans('success_delete'));

        return $this->redirectToRoute('backoffice_larp_participant_list', [
            'larp' => $larp->getId(),
        ]);
    }
}
