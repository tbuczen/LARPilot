<?php

namespace App\Controller\Backoffice\Story;

use App\Controller\BaseController;
use App\Entity\Larp;
use App\Entity\StoryObject\Character;
use App\Entity\Tag;
use App\Form\Filter\MarketplaceFilterType;
use App\Repository\LarpApplicationRepository;
use App\Repository\StoryObject\CharacterRepository;
use App\Repository\StoryObject\EventRepository;
use App\Repository\StoryObject\QuestRepository;
use App\Repository\StoryObject\ThreadRepository;
use App\Service\StoryObject\MarketplaceService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}/story/marketplace/', name: 'backoffice_larp_story_marketplace_')]
class StoryMarketplaceController extends BaseController
{
    #[Route('list', name: 'list', methods: ['GET'])]
    public function list(
        Larp $larp,
        Request $request,
        MarketplaceService $marketplaceService,
        ThreadRepository $threadRepository,
        QuestRepository $questRepository,
        EventRepository $eventRepository,
        CharacterRepository $characterRepository
    ): Response {
        $filterForm = $this->createForm(MarketplaceFilterType::class, null, ['larp' => $larp]);
        $filterForm->handleRequest($request);

        $view = $request->query->get('view', 'threads'); // threads, quests, events, characters
        $selectedTags = [];

        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $selectedTags = $filterForm->get('tags')->getData();
        }

        $data = match ($view) {
            'threads' => [
                'items' => $selectedTags
                    ? $marketplaceService->searchThreadsByTags($larp, $selectedTags->toArray())
                    : $threadRepository->findBy(['larp' => $larp], ['title' => 'ASC']),
                'type' => 'threads',
            ],
            'quests' => [
                'items' => $selectedTags
                    ? $questRepository->createQueryBuilder('q')
                        ->join('q.tags', 'tag')
                        ->where('q.larp = :larp')
                        ->andWhere('tag IN (:tags)')
                        ->setParameter('larp', $larp)
                        ->setParameter('tags', $selectedTags)
                        ->getQuery()
                        ->getResult()
                    : $questRepository->findBy(['larp' => $larp], ['title' => 'ASC']),
                'type' => 'quests',
            ],
            'events' => [
                'items' => $selectedTags
                    ? $eventRepository->createQueryBuilder('e')
                        ->join('e.tags', 'tag')
                        ->where('e.larp = :larp')
                        ->andWhere('tag IN (:tags)')
                        ->setParameter('larp', $larp)
                        ->setParameter('tags', $selectedTags)
                        ->getQuery()
                        ->getResult()
                    : $eventRepository->findBy(['larp' => $larp], ['title' => 'ASC']),
                'type' => 'events',
            ],
            'characters' => [
                'items' => $marketplaceService->getCharactersNeedingThreads($larp),
                'type' => 'characters',
            ],
            default => [
                'items' => [],
                'type' => 'threads',
            ],
        };

        return $this->render('backoffice/larp/marketplace/list.html.twig', [
            'larp' => $larp,
            'items' => $data['items'],
            'type' => $data['type'],
            'view' => $view,
            'filterForm' => $filterForm->createView(),
            'minThreadsPerCharacter' => $larp->getMinThreadsPerCharacter(),
        ]);
    }

    #[Route('character/{character}', name: 'character_detail', methods: ['GET'])]
    public function characterDetail(
        Larp $larp,
        Character $character,
        MarketplaceService $marketplaceService
    ): Response {
        // Get character's current threads
        $currentThreads = $character->getThreads();
        $threadCount = $currentThreads->count();
        $minThreads = $larp->getMinThreadsPerCharacter();
        $needsMoreThreads = $threadCount < $minThreads;

        // Get character's tags for suggestions
        $characterTags = $character->getTags()->toArray();

        // Find threads that match character's tags
        $suggestedThreads = $marketplaceService->searchThreadsByTags($larp, $characterTags);

        return $this->render('backoffice/larp/marketplace/character_detail.html.twig', [
            'larp' => $larp,
            'character' => $character,
            'currentThreads' => $currentThreads,
            'threadCount' => $threadCount,
            'minThreads' => $minThreads,
            'needsMoreThreads' => $needsMoreThreads,
            'suggestedThreads' => $suggestedThreads,
        ]);
    }

    #[Route('suggestions/{application}', name: 'suggestions', methods: ['GET'])]
    public function suggestions(
        Larp $larp,
        Request $request,
        MarketplaceService $marketplaceService,
        LarpApplicationRepository $applicationRepository
    ): Response {
        $applicationId = $request->attributes->get('application');
        $application = $applicationRepository->find($applicationId);

        if (!$application || $application->getLarp()->getId() !== $larp->getId()) {
            throw $this->createNotFoundException('Application not found');
        }

        $view = $request->query->get('view', 'threads');

        $suggestions = match ($view) {
            'threads' => $marketplaceService->getSuggestedThreadsForApplication($application),
            'quests' => $marketplaceService->getSuggestedQuestsForApplication($application),
            'events' => $marketplaceService->getSuggestedEventsForApplication($application),
            default => [],
        };

        return $this->render('backoffice/larp/marketplace/suggestions.html.twig', [
            'larp' => $larp,
            'application' => $application,
            'suggestions' => $suggestions,
            'view' => $view,
        ]);
    }

    #[Route('search-characters', name: 'search_characters', methods: ['GET'])]
    public function searchCharacters(
        Larp $larp,
        Request $request,
        MarketplaceService $marketplaceService
    ): Response {
        $tagIds = $request->query->all('tags');
        $onlyNeedingThreads = $request->query->getBoolean('only_needing_threads', false);

        $tags = [];
        if ($tagIds) {
            // Get tags from IDs
            $tags = $this->getDoctrine()->getRepository(Tag::class)->findBy(['id' => $tagIds]);
        }

        $characters = $marketplaceService->searchCharactersByTags($larp, $tags, $onlyNeedingThreads);

        return $this->render('backoffice/larp/marketplace/character_search_results.html.twig', [
            'larp' => $larp,
            'characters' => $characters,
            'minThreadsPerCharacter' => $larp->getMinThreadsPerCharacter(),
        ]);
    }
}
