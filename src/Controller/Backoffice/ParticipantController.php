<?php

namespace App\Controller\Backoffice;

use App\Controller\BaseController;
use App\Entity\Larp;
use App\Entity\LarpParticipant;
use App\Form\Filter\ParticipantFilterType;
use App\Form\ParticipantType;
use App\Repository\LarpParticipantRepository;
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
        $participants = $qb->getQuery()->getResult();

        $this->entityPreloader->preload($participants, 'user');
        $this->entityPreloader->preload($participants, 'larpCharacters');

        return $this->render('backoffice/larp/participant/list.html.twig', [
            'larp' => $larp,
            'participants' => $participants,
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
        $form = $this->createForm(ParticipantType::class, $participant, ['larp' => $larp]);
        $form->handleRequest($request);

        if (!$participant) {
            $participant = new LarpParticipant();
        } else {
            $this->entityPreloader->preload([$participant], 'larpCharacters');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $participant->setLarp($larp);
            $participantRepository->save($participant);
            $this->addFlash('success', $this->translator->trans('backoffice.common.success_save'));
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
        $this->addFlash('success', $this->translator->trans('backoffice.common.success_delete'));

        return $this->redirectToRoute('backoffice_larp_participant_list', [
            'larp' => $larp->getId(),
        ]);
    }
}
