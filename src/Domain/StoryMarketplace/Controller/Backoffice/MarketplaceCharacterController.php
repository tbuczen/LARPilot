<?php

namespace App\Domain\StoryMarketplace\Controller\Backoffice;

use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Entity\Larp;
use App\Domain\StoryMarketplace\Service\MarketplaceService;
use App\Domain\StoryObject\Entity\Character;
use App\Domain\StoryObject\Form\Filter\CharacterFilterType;
use App\Domain\StoryObject\Repository\CharacterRepository;
use App\Domain\StoryObject\Repository\ThreadRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}/story/marketplace/characters', name: 'backoffice_larp_story_marketplace_characters_')]
class MarketplaceCharacterController extends BaseController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(
        Larp $larp,
        Request $request,
        CharacterRepository $characterRepository
    ): Response {
        $filterForm = $this->createForm(CharacterFilterType::class, null, ['larp' => $larp]);
        $filterForm->handleRequest($request);

        $qb = $characterRepository->createCharactersNeedingThreadsQueryBuilder(
            $larp,
            $larp->getMinThreadsPerCharacter()
        );

        // Apply filters using FilterBuilderUpdater
        $this->filterBuilderUpdater->addFilterConditions($filterForm, $qb);

        $pagination = $this->getPagination($qb, $request);

        return $this->render('backoffice/larp/marketplace/characters/list.html.twig', [
            'larp' => $larp,
            'characters' => $pagination,
            'minThreadsPerCharacter' => $larp->getMinThreadsPerCharacter(),
            'filterForm' => $filterForm->createView(),
        ]);
    }

    #[Route('/{character}', name: 'detail', methods: ['GET'])]
    public function detail(
        Larp $larp,
        Character $character,
        MarketplaceService $marketplaceService,
        ThreadRepository $threadRepository
    ): Response {
        // Get character's current threads
        $currentThreads = $character->getThreads();
        $threadCount = $currentThreads->count();
        $minThreads = $larp->getMinThreadsPerCharacter();
        $needsMoreThreads = $threadCount < $minThreads;

        // Get character's tags for suggestions
        $characterTags = $character->getTags()->toArray();

        // Find threads that match the character's tags
        $suggestedThreads = $threadRepository->createThreadsByTagsQueryBuilder($larp, $characterTags);

        return $this->render('backoffice/larp/marketplace/characters/detail.html.twig', [
            'larp' => $larp,
            'character' => $character,
            'currentThreads' => $currentThreads,
            'threadCount' => $threadCount,
            'minThreads' => $minThreads,
            'needsMoreThreads' => $needsMoreThreads,
            'suggestedThreads' => $suggestedThreads,
        ]);
    }
}
