<?php

namespace App\Controller\Backoffice;

use App\Controller\BaseController;
use App\Entity\Larp;
use App\Repository\LarpCharacterSubmissionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}/submissions', name: 'backoffice_larp_submissions_')]
class LarpCharacterSubmissionsController extends BaseController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Larp $larp, LarpCharacterSubmissionRepository $repository): Response
    {
        $submissions = $repository->findBy(['larp' => $larp]);

        $charactersWithSubmission = [];
        foreach ($submissions as $submission) {
            foreach ($submission->getChoices() as $choice) {
                $charactersWithSubmission[$choice->getCharacter()->getId()->toRfc4122()] = true;
            }
        }

        $missing = 0;
        foreach ($larp->getCharacters() as $character) {
            if (!isset($charactersWithSubmission[$character->getId()->toRfc4122()])) {
                $missing++;
            }
        }

        $factionStats = [];
        foreach ($larp->getFactions() as $faction) {
            $total = count($faction->getMembers());
            if ($total === 0) {
                continue;
            }
            $with = 0;
            foreach ($faction->getMembers() as $member) {
                if (isset($charactersWithSubmission[$member->getId()->toRfc4122()])) {
                    $with++;
                }
            }
            $factionStats[] = [
                'faction' => $faction,
                'percentage' => round($with / $total * 100, 2),
            ];
        }

        return $this->render('backoffice/larp/submission/list.html.twig', [
            'larp' => $larp,
            'submissions' => $submissions,
            'missing' => $missing,
            'factionStats' => $factionStats,
        ]);
    }
}
