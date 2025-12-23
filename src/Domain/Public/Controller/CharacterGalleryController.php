<?php

declare(strict_types=1);

namespace App\Domain\Public\Controller;

use App\Domain\Core\Entity\Larp;
use App\Domain\Public\Form\Filter\CharacterGalleryFilterType;
use App\Domain\StoryObject\Entity\Character;
use App\Domain\StoryObject\Entity\Enum\CharacterType;
use App\Domain\StoryObject\Repository\CharacterRepository;
use Spiriit\Bundle\FormFilterBundle\Filter\FilterBuilderUpdaterInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{slug}/characters', name: 'public_larp_characters_')]
class CharacterGalleryController extends AbstractController
{
    public function __construct(
        private readonly FilterBuilderUpdaterInterface $filterBuilderUpdater
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(
        Request $request,
        Larp $larp,
        CharacterRepository $characterRepository
    ): Response {
        // Check if characters are published publicly
        if (!$larp->getPublishCharactersPublicly()) {
            throw $this->createAccessDeniedException('Characters are not publicly available for this LARP.');
        }

        // Check LARP is visible
        if (!$larp->getStatus()?->isVisibleForEveryone()) {
            throw $this->createAccessDeniedException('This LARP is not publicly visible.');
        }

        // Build base query - only show Player characters or those available for recruitment
        $qb = $characterRepository->createQueryBuilder('c')
            ->where('c.larp = :larp')
            ->andWhere('(c.characterType = :playerType OR c.availableForRecruitment = true)')
            ->setParameter('larp', $larp)
            ->setParameter('playerType', CharacterType::Player);

        // Apply filters
        $filterForm = $this->createForm(CharacterGalleryFilterType::class, null, ['larp' => $larp]);
        $filterForm->handleRequest($request);
        $this->filterBuilderUpdater->addFilterConditions($filterForm, $qb);

        // Get characters
        $characters = $qb->getQuery()->getResult();

        return $this->render('public/character/gallery.html.twig', [
            'larp' => $larp,
            'characters' => $characters,
            'filterForm' => $filterForm->createView(),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(
        Larp $larp,
        Character $character
    ): Response {
        // Check if characters are published publicly
        if (!$larp->getPublishCharactersPublicly()) {
            throw $this->createAccessDeniedException('Characters are not publicly available for this LARP.');
        }

        // Check LARP is visible
        if (!$larp->getStatus()?->isVisibleForEveryone()) {
            throw $this->createAccessDeniedException('This LARP is not publicly visible.');
        }

        // Verify character belongs to this LARP
        if ($character->getLarp() !== $larp) {
            throw $this->createNotFoundException('Character not found for this LARP.');
        }

        // Verify character is eligible for public viewing
        if ($character->getCharacterType() !== CharacterType::Player) {
            throw $this->createAccessDeniedException('This character is not publicly available.');
        }

        return $this->render('public/character/show.html.twig', [
            'larp' => $larp,
            'character' => $character,
        ]);
    }
}
