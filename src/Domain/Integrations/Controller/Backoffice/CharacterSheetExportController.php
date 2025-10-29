<?php

namespace App\Domain\Integrations\Controller\Backoffice;

use App\Domain\Core\Entity\Larp;
use App\Domain\Integrations\Service\CharacterSheetExportService;
use App\Domain\StoryObject\Entity\Character;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/larp/{larp}/character', name: 'backoffice_larp_character_')]
class CharacterSheetExportController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route('/{character}/export-sheet', name: 'export_sheet', methods: ['POST'])]
    public function exportSheet(
        Larp $larp,
        Character $character,
        CharacterSheetExportService $exportService,
    ): Response {
        try {
            $result = $exportService->exportCharacterSheet($character);

            if ($result['existed']) {
                $this->addFlash('success', $this->translator->trans('larp.character.export.updated', [
                    '%url%' => $result['documentUrl'],
                ], 'forms'));
            } else {
                $this->addFlash('success', $this->translator->trans('larp.character.export.created', [
                    '%url%' => $result['documentUrl'],
                ], 'forms'));
            }

            return $this->redirectToRoute('backoffice_larp_story_character_modify', [
                'larp' => $larp->getId(),
                'character' => $character->getId(),
            ]);
        } catch (\RuntimeException $e) {
            $this->addFlash('error', $this->translator->trans('larp.character.export.error', [
                '%message%' => $e->getMessage(),
            ], 'forms'));

            return $this->redirectToRoute('backoffice_larp_story_character_modify', [
                'larp' => $larp->getId(),
                'character' => $character->getId(),
            ]);
        }
    }
}
