<?php

namespace App\Controller\Backoffice\Story;

use App\Controller\BaseController;
use App\Entity\Enum\RecruitmentProposalStatus;
use App\Entity\Larp;
use App\Entity\StoryObject\Event;
use App\Entity\StoryObject\RecruitmentProposal;
use App\Entity\StoryObject\StoryRecruitment;
use App\Form\EventType;
use App\Form\Filter\EventFilterType;
use App\Form\RecruitmentProposalType;
use App\Form\StoryRecruitmentType;
use App\Helper\Logger;
use App\Repository\StoryObject\EventRepository;
use App\Repository\StoryObject\RecruitmentProposalRepository;
use App\Repository\StoryObject\StoryRecruitmentRepository;
use App\Service\Integrations\IntegrationManager;
use App\Service\Larp\LarpManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}/story/event/', name: 'backoffice_larp_story_event_')]

class EventsController extends BaseController
{
    #[Route('list', name: 'list', methods: ['GET', 'POST'])]
    public function list(Request $request, Larp $larp, EventRepository $repository): Response
    {
        $filterForm = $this->createForm(EventFilterType::class, null, ['larp' => $larp]);
        $filterForm->handleRequest($request);
        $qb = $repository->createQueryBuilder('c');
        $this->filterBuilderUpdater->addFilterConditions($filterForm, $qb);
        $sort = $request->query->get('sort', 'title');
        $dir = $request->query->get('dir', 'asc');

        $qb->orderBy('c.' . $sort, $dir);
        $qb->andWhere('c.larp = :larp')
            ->setParameter('larp', $larp);

        return $this->render('backoffice/larp/event/list.html.twig', [
            'filterForm' => $filterForm->createView(),
            'events' => $qb->getQuery()->getResult(),
            'larp' => $larp,
        ]);
    }

    #[Route('{event}', name: 'modify', defaults: ['event' => null], methods: ['GET', 'POST'])]
    public function modify(
        LarpManager        $larpManager,
        IntegrationManager $integrationManager,
        Request            $request,
        Larp               $larp,
        EventRepository    $eventRepository,
        ?Event             $event = null,
    ): Response {
        $new = false;
        if (!$event) {
            $event = new Event();
            $event->setLarp($larp);
            $new = true;
        }

        $form = $this->createForm(EventType::class, $event, ['larp' => $larp]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $eventRepository->save($event);

            $this->processIntegrationsForStoryObject($larpManager, $larp, $integrationManager, $new, $event);

            $this->addFlash('success', $this->translator->trans('backoffice.common.success_save'));
            return $this->redirectToRoute('backoffice_larp_story_event_list', ['larp' => $larp->getId()]);
        }

        return $this->render('backoffice/larp/events/modify.html.twig', [
            'form' => $form->createView(),
            'larp' => $larp,
        ]);
    }

    #[Route('{event}/delete', name: 'delete', methods: ['GET', 'POST'])]
    public function delete(
        LarpManager             $larpManager,
        IntegrationManager      $integrationManager,
        Larp                    $larp,
        Request                 $request,
        EventRepository $eventRepository,
        Event           $event,
    ): Response {
        $deleteIntegrations = $request->query->getBoolean('integrations');

        if ($deleteIntegrations) {
            if (!$this->removeStoryObjectFromIntegrations($larpManager, $larp, $integrationManager, $event, 'Event')) {
                return $this->redirectToRoute('backoffice_larp_story_event_list', [
                    'larp' => $larp->getId(),
                ]);
            }
        }

        $eventRepository->remove($event);

        $this->addFlash('success', $this->translator->trans('backoffice.common.success_delete'));

        return $this->redirectToRoute('backoffice_larp_story_event_list', [
            'larp' => $larp->getId(),
        ]);
    }

    #[Route('import/file', name: 'import_file', methods: ['GET', 'POST'])]
    public function importFile(Larp $larp, LarpManager $larpManager): Response
    {
        return new Response('TODO:: Import from file csv/xlsx');
    }

    #[Route('recruitments', name: 'recruitment_list', methods: ['GET'])]
    public function recruitmentList(Larp $larp, StoryRecruitmentRepository $recruitmentRepository): Response
    {
        $recruitments = $recruitmentRepository->createQueryBuilder('r')
            ->join('r.storyObject', 'o')
            ->andWhere('o INSTANCE OF ' . Event::class)
            ->andWhere('o.larp = :larp')
            ->setParameter('larp', $larp)
            ->getQuery()
            ->getResult();

        return $this->render('backoffice/larp/recruitment/list.html.twig', [
            'recruitments' => $recruitments,
            'larp' => $larp,
            'modify_route' => 'backoffice_larp_story_event_recruitment',
            'proposal_route' => 'backoffice_larp_story_event_proposal',
        ]);
    }

    #[Route('recruitment/{recruitment}/proposals', name: 'proposal_list', methods: ['GET'])]
    public function proposalList(StoryRecruitment $recruitment): Response
    {
        return $this->render('backoffice/larp/proposal/list.html.twig', [
            'proposals' => $recruitment->getProposals(),
            'larp' => $recruitment->getStoryObject()->getLarp(),
            'accept_route' => 'backoffice_larp_story_event_proposal_accept',
            'reject_route' => 'backoffice_larp_story_event_proposal_reject',
            'create_route' => 'backoffice_larp_story_event_proposal',
            'recruitment' => $recruitment,
        ]);
    }

    #[Route('recruitment/{recruitment}/proposal', name: 'proposal', methods: ['GET', 'POST'])]
    public function proposal(
        Request                      $request,
        Larp                         $larp,
        StoryRecruitment             $recruitment,
        RecruitmentProposalRepository $proposalRepository,
    ): Response {
        $proposal = new RecruitmentProposal();
        $proposal->setRecruitment($recruitment);

        $form = $this->createForm(RecruitmentProposalType::class, $proposal, ['larp' => $larp]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $proposalRepository->save($proposal);

            return $this->redirectToRoute('backoffice_larp_story_event_proposal_list', [
                'larp' => $larp->getId(),
                'recruitment' => $recruitment->getId(),
            ]);
        }

        return $this->render('backoffice/larp/proposal/modify.html.twig', [
            'form' => $form->createView(),
            'larp' => $larp,
        ]);
    }

    #[Route('{event}/recruitment', name: 'recruitment', defaults: ['recruitment' => null], methods: ['GET', 'POST'])]
    public function recruitment(
        Request                    $request,
        Larp                       $larp,
        Event                      $event,
        StoryRecruitmentRepository $recruitmentRepository,
        ?StoryRecruitment          $recruitment = null,
    ): Response {
        if (!$recruitment) {
            $recruitment = new StoryRecruitment();
            $recruitment->setStoryObject($event);
            $recruitment->setCreatedBy($this->getUser());
        }

        $form = $this->createForm(StoryRecruitmentType::class, $recruitment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $recruitmentRepository->save($recruitment);
            $this->addFlash('success', $this->translator->trans('backoffice.common.success_save'));

            return $this->redirectToRoute('backoffice_larp_story_event_list', [
                'larp' => $larp->getId(),
            ]);
        }

        return $this->render('backoffice/larp/recruitment/modify.html.twig', [
            'form' => $form->createView(),
            'larp' => $larp,
        ]);
    }

    #[Route('proposal/{proposal}/accept', name: 'proposal_accept', methods: ['POST'])]
    public function acceptProposal(RecruitmentProposal $proposal, RecruitmentProposalRepository $proposalRepository): Response
    {
        $proposal->setStatus(RecruitmentProposalStatus::ACCEPTED);
        $proposalRepository->save($proposal);

        return $this->redirectToRoute('backoffice_larp_story_event_list', [
            'larp' => $proposal->getRecruitment()->getStoryObject()->getLarp()->getId(),
        ]);
    }

    #[Route('proposal/{proposal}/reject', name: 'proposal_reject', methods: ['POST'])]
    public function rejectProposal(RecruitmentProposal $proposal, RecruitmentProposalRepository $proposalRepository): Response
    {
        $proposal->setStatus(RecruitmentProposalStatus::REJECTED);
        $proposalRepository->save($proposal);

        return $this->redirectToRoute('backoffice_larp_story_event_list', [
            'larp' => $proposal->getRecruitment()->getStoryObject()->getLarp()->getId(),
        ]);
    }
}
