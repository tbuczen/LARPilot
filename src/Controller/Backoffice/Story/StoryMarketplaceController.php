<?php

namespace App\Controller\Backoffice\Story;

use App\Controller\BaseController;
use App\Entity\Larp;
use App\Entity\StoryObject\Character;
use App\Repository\StoryObject\CharacterRepository;
use App\Repository\StoryObject\StoryRecruitmentRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}/story/marketplace/', name: 'backoffice_larp_story_marketplace_')]
class StoryMarketplaceController extends BaseController
{
    #[Route('list', name: 'list', methods: ['GET'])]
    public function list(Larp $larp, CharacterRepository $repository): Response
    {
        $characters = $repository->createQueryBuilder('c')
            ->andWhere('c.larp = :larp')
            ->andWhere('c.availableForRecruitment = true')
            ->setParameter('larp', $larp)
            ->orderBy('c.title', 'asc')
            ->getQuery()
            ->getResult();

        return $this->render('backoffice/larp/marketplace/list.html.twig', [
            'larp' => $larp,
            'characters' => $characters,
        ]);
    }

    #[Route('toggle/{character}', name: 'toggle', methods: ['POST'])]
    public function toggle(Larp $larp, Character $character, CharacterRepository $repository): Response
    {
        $character->setAvailableForRecruitment(!$character->isAvailableForRecruitment());
        $repository->save($character);

        return $this->redirectToRoute('backoffice_larp_story_marketplace_list', [
            'larp' => $larp->getId(),
        ]);
    }

    #[Route('{character}/recruitments', name: 'recruitments', methods: ['GET'])]
    public function recruitments(Larp $larp, Character $character, StoryRecruitmentRepository $recruitmentRepository): Response
    {
        $recruitments = $recruitmentRepository->createQueryBuilder('r')
            ->join('r.storyObject', 'o')
            ->andWhere('o.larp = :larp')
            ->setParameter('larp', $larp)
            ->getQuery()
            ->getResult();

        return $this->render('backoffice/larp/marketplace/recruitments.html.twig', [
            'larp' => $larp,
            'character' => $character,
            'recruitments' => $recruitments,
        ]);
    }
}
