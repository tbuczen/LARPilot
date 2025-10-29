<?php

namespace App\Domain\StoryObject\Controller\Backoffice;

use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Service\LarpManager;
use App\Domain\Integrations\Service\IntegrationManager;
use App\Domain\StoryMarketplace\Entity\Enum\RecruitmentProposalStatus;
use App\Domain\StoryMarketplace\Entity\RecruitmentProposal;
use App\Domain\StoryMarketplace\Entity\StoryRecruitment;
use App\Domain\StoryMarketplace\Form\RecruitmentProposalType;
use App\Domain\StoryMarketplace\Form\StoryRecruitmentType;
use App\Domain\StoryMarketplace\Repository\RecruitmentProposalRepository;
use App\Domain\StoryMarketplace\Repository\StoryRecruitmentRepository;
use App\Domain\StoryObject\Entity\Enum\EventCategory;
use App\Domain\StoryObject\Entity\Event;
use App\Domain\StoryObject\Form\EventType;
use App\Domain\StoryObject\Form\Filter\EventFilterType;
use App\Domain\StoryObject\Form\Filter\EventTimelineFilterType;
use App\Domain\StoryObject\Repository\EventRepository;
use App\Domain\StoryObject\Service\StoryObjectMentionService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}/story/event/', name: 'backoffice_larp_story_event_')]

class EventController extends BaseController
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

    #[Route('timeline', name: 'timeline', methods: ['GET'])]
    public function timeline(Request $request, Larp $larp, EventRepository $repository): Response
    {
        $filterForm = $this->createForm(EventTimelineFilterType::class, null, ['larp' => $larp]);
        $filterForm->handleRequest($request);

        $qb = $repository->createQueryBuilder('e')
            ->where('e.larp = :larp')
            ->setParameter('larp', $larp);

        // Apply filter conditions
        $this->filterBuilderUpdater->addFilterConditions($filterForm, $qb);

        // Order by story time, then start time
        $qb->orderBy('e.storyTime', 'ASC')
            ->addOrderBy('e.startTime', 'ASC');

        $events = $qb->getQuery()->getResult();

        // Normalize events for JSON serialization
        $normalizedEvents = array_map(function (Event $event) {
            return [
                'id' => $event->getId()->toRfc4122(),
                'title' => $event->getTitle(),
                'description' => $event->getDescription(),
                'category' => $event->getCategory()->value,
                'storyTime' => $event->getStoryTime(),
                'storyTimeUnit' => $event->getStoryTimeUnit()?->value,
                'startTime' => $event->getStartTime()?->format('c'),
                'endTime' => $event->getEndTime()?->format('c'),
                'isPublic' => $event->isPublic(),
                'knownPublicly' => $event->isKnownPublicly(),
                'involvedFactions' => $event->getInvolvedFactions()->map(fn ($f) => [
                    'id' => $f->getId()->toRfc4122(),
                    'title' => $f->getTitle(),
                ])->toArray(),
                'involvedCharacters' => $event->getInvolvedCharacters()->map(fn ($c) => [
                    'id' => $c->getId()->toRfc4122(),
                    'title' => $c->getTitle(),
                ])->toArray(),
            ];
        }, $events);

        return $this->render('backoffice/larp/event/timeline.html.twig', [
            'larp' => $larp,
            'events' => $normalizedEvents,
            'filterForm' => $filterForm->createView(),
        ]);
    }

    #[Route('api/create', name: 'api_create', methods: ['POST'])]
    public function apiCreate(
        Request $request,
        Larp $larp,
        EventRepository $eventRepository
    ): Response {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['title'])) {
            return $this->json(['error' => 'Title is required'], 400);
        }

        $event = new Event();
        $event->setLarp($larp);
        $event->setTitle($data['title']);

        // Set category if provided
        if (isset($data['category'])) {
            try {
                $category = EventCategory::from($data['category']);
                $event->setCategory($category);
            } catch (\ValueError $e) {
                // Invalid category, use default
            }
        }

        // Set description if provided
        if (isset($data['description'])) {
            $event->setDescription($data['description']);
        }

        // Set story time if provided
        if (isset($data['storyTime'])) {
            $event->setStoryTime((int)$data['storyTime']);
        }

        // Set start time if provided
        if (isset($data['startTime'])) {
            try {
                $startTime = new \DateTime($data['startTime']);
                $event->setStartTime($startTime);
            } catch (\Exception $e) {
                // Invalid date format
            }
        }

        $eventRepository->save($event);

        return $this->json([
            'success' => true,
            'event' => [
                'id' => $event->getId()->toRfc4122(),
                'title' => $event->getTitle(),
                'description' => $event->getDescription(),
                'category' => $event->getCategory()->value,
                'storyTime' => $event->getStoryTime(),
                'storyTimeUnit' => $event->getStoryTimeUnit()?->value,
                'startTime' => $event->getStartTime()?->format('c'),
                'endTime' => $event->getEndTime()?->format('c'),
                'isPublic' => $event->isPublic(),
                'knownPublicly' => $event->isKnownPublicly(),
                'involvedFactions' => [],
                'involvedCharacters' => [],
            ],
        ]);
    }

    #[Route('{event}/api/update-time', name: 'api_update_time', methods: ['POST'])]
    public function apiUpdateTime(
        Request $request,
        Larp $larp,
        Event $event,
        EventRepository $eventRepository
    ): Response {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['startTime'])) {
            return $this->json(['error' => 'Start time is required'], 400);
        }

        try {
            $startTime = new \DateTime($data['startTime']);
            $event->setStartTime($startTime);

            // Update end time if provided
            if (isset($data['endTime'])) {
                $endTime = new \DateTime($data['endTime']);
                $event->setEndTime($endTime);
            }

            $eventRepository->save($event);

            return $this->json([
                'success' => true,
                'event' => [
                    'id' => $event->getId()->toRfc4122(),
                    'startTime' => $event->getStartTime()?->format('c'),
                    'endTime' => $event->getEndTime()?->format('c'),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Invalid date format'], 400);
        }
    }

    #[Route('{event}', name: 'modify', defaults: ['event' => null], methods: ['GET', 'POST'])]
    public function modify(
        LarpManager        $larpManager,
        IntegrationManager $integrationManager,
        StoryObjectMentionService $mentionService,
        Request            $request,
        Larp               $larp,
        EventRepository    $eventRepository,
        ?Event             $event = null,
    ): Response {
        $new = false;
        if (!$event instanceof Event) {
            $event = new Event();
            $event->setLarp($larp);
            $new = true;
        }

        $form = $this->createForm(EventType::class, $event, ['larp' => $larp]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $eventRepository->save($event);

            $this->processIntegrationsForStoryObject($larpManager, $larp, $integrationManager, $new, $event);

            $this->addFlash('success', $this->translator->trans('success_save'));
            return $this->redirectToRoute('backoffice_larp_story_event_list', ['larp' => $larp->getId()]);
        }

        // Get mentions only for existing events (not new ones)
        $mentions = [];
        if ($event->getId() !== null) {
            $mentions = $mentionService->findMentions($event);
        }

        return $this->render('backoffice/larp/event/modify.html.twig', [
            'form' => $form->createView(),
            'larp' => $larp,
            'event' => $event,
            'mentions' => $mentions,
        ]);
    }

    #[Route('{event}/mentions', name: 'mentions', methods: ['GET'])]
    public function mentions(
        Larp                      $larp,
        Event                     $event,
        StoryObjectMentionService $mentionService,
    ): Response {
        $mentions = $mentionService->findMentions($event);

        return $this->render('backoffice/larp/event/mentions.html.twig', [
            'larp' => $larp,
            'event' => $event,
            'mentions' => $mentions,
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

        if ($deleteIntegrations && !$this->removeStoryObjectFromIntegrations($larpManager, $larp, $integrationManager, $event, 'Event')) {
            return $this->redirectToRoute('backoffice_larp_story_event_list', [
                'larp' => $larp->getId(),
            ]);
        }

        $eventRepository->remove($event);

        $this->addFlash('success', $this->translator->trans('success_delete'));

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
        if (!$recruitment instanceof StoryRecruitment) {
            $recruitment = new StoryRecruitment();
            $recruitment->setStoryObject($event);
            $recruitment->setCreatedBy($this->getUser());
        }

        $form = $this->createForm(StoryRecruitmentType::class, $recruitment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $recruitmentRepository->save($recruitment);
            $this->addFlash('success', $this->translator->trans('success_save'));

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
