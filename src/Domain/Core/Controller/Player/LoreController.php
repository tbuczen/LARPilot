<?php

namespace App\Domain\Core\Controller\Player;

use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Repository\LarpParticipantRepository;
use App\Domain\StoryObject\Entity\Event;
use App\Domain\StoryObject\Form\Filter\PlayerEventTimelineFilterType;
use App\Domain\StoryObject\Repository\EventRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/larp/{larp}/lore', name: 'player_lore_')]
#[IsGranted('ROLE_USER')]
class LoreController extends BaseController
{
    #[Route('/timeline', name: 'timeline', methods: ['GET'])]
    public function timeline(
        Request $request,
        Larp $larp,
        EventRepository $eventRepository,
        LarpParticipantRepository $participantRepository
    ): Response {
        $user = $this->getUser();

        // Get participant for this LARP
        $participant = $participantRepository->findOneBy([
            'larp' => $larp,
            'user' => $user,
        ]);

        if (!$participant) {
            $this->addFlash('error', 'You are not a participant of this LARP.');
            return $this->redirectToRoute('public_larp_list');
        }

        // Create filter form
        $filterForm = $this->createForm(PlayerEventTimelineFilterType::class, null, [
            'larp' => $larp,
            'participant' => $participant,
        ]);
        $filterForm->handleRequest($request);

        // Build query with visibility filtering
        $qb = $eventRepository->createTimelineQueryBuilder($larp, $participant);

        // Apply filter conditions from form
        $filterData = $filterForm->getData();
        
        if ($filterData) {
            if (isset($filterData['category']) && $filterData['category']) {
                $qb->andWhere('e.category = :category')
                    ->setParameter('category', $filterData['category']);
            }

            if (isset($filterData['character']) && $filterData['character']) {
                $qb->andWhere(':characterFilter MEMBER OF e.involvedCharacters')
                    ->setParameter('characterFilter', $filterData['character']);
            }

            if (isset($filterData['faction']) && $filterData['faction']) {
                $qb->andWhere(':factionFilter MEMBER OF e.involvedFactions')
                    ->setParameter('factionFilter', $filterData['faction']);
            }
        }

        // Order by story time, then by start time
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

        return $this->render('player/lore/timeline.html.twig', [
            'larp' => $larp,
            'participant' => $participant,
            'events' => $normalizedEvents,
            'filterForm' => $filterForm->createView(),
        ]);
    }
}
