<?php

declare(strict_types=1);

namespace App\Domain\Survey\Controller\Backoffice;

use App\Domain\Application\Entity\Enum\SubmissionStatus;
use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Entity\Larp;
use App\Domain\StoryObject\Entity\Character;
use App\Domain\Survey\Entity\SurveyResponse;
use App\Domain\Survey\Repository\SurveyResponseRepository;
use App\Domain\Survey\Service\CharacterMatchingService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}/survey/responses', name: 'backoffice_larp_survey_responses_')]
class SurveyResponseController extends BaseController
{
    public function __construct(
        private readonly CharacterMatchingService $matchingService
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(
        Larp $larp,
        SurveyResponseRepository $responseRepository
    ): Response {
        $responses = $responseRepository->findByLarpWithRelations($larp);

        return $this->render('backoffice/survey/responses/list.html.twig', [
            'larp' => $larp,
            'responses' => $responses,
        ]);
    }

    #[Route('/{response}', name: 'show', methods: ['GET'])]
    public function show(
        Larp $larp,
        SurveyResponse $response
    ): Response {
        // Verify response belongs to this LARP
        if ($response->getLarp() !== $larp) {
            throw $this->createAccessDeniedException();
        }

        // Get or generate match suggestions
        $matchSuggestions = $response->getMatchSuggestions();
        if ($matchSuggestions === null || $matchSuggestions === []) {
            $matchSuggestions = $this->matchingService->generateMatchSuggestions($response);
            $response->setMatchSuggestions($matchSuggestions);
        }

        return $this->render('backoffice/survey/responses/show.html.twig', [
            'larp' => $larp,
            'response' => $response,
            'matchSuggestions' => $matchSuggestions,
        ]);
    }

    #[Route('/{response}/assign/{character}', name: 'assign_character', methods: ['POST'])]
    public function assignCharacter(
        Larp $larp,
        SurveyResponse $response,
        Character $character,
        SurveyResponseRepository $responseRepository
    ): Response {
        // Verify response belongs to this LARP
        if ($response->getLarp() !== $larp || $character->getLarp() !== $larp) {
            throw $this->createAccessDeniedException();
        }

        $response->setAssignedCharacter($character);
        $response->setStatus(SubmissionStatus::OFFERED);
        $responseRepository->save($response);

        $this->addFlash('success', $this->translator->trans('survey.response.character_assigned', [
            '%character%' => $character->getTitle(),
            '%user%' => $response->getUser()->getContactEmail(),
        ]));

        return $this->redirectToRoute('backoffice_larp_survey_responses_show', [
            'larp' => $larp->getId(),
            'response' => $response->getId(),
        ]);
    }

    #[Route('/{response}/regenerate-matches', name: 'regenerate_matches', methods: ['POST'])]
    public function regenerateMatches(
        Larp $larp,
        SurveyResponse $response,
        SurveyResponseRepository $responseRepository
    ): Response {
        // Verify response belongs to this LARP
        if ($response->getLarp() !== $larp) {
            throw $this->createAccessDeniedException();
        }

        $matchSuggestions = $this->matchingService->generateMatchSuggestions($response);
        $response->setMatchSuggestions($matchSuggestions);
        $responseRepository->save($response);

        $this->addFlash('success', $this->translator->trans('survey.response.matches_regenerated'));

        return $this->redirectToRoute('backoffice_larp_survey_responses_show', [
            'larp' => $larp->getId(),
            'response' => $response->getId(),
        ]);
    }

    #[Route('/regenerate-all', name: 'regenerate_all', methods: ['POST'])]
    public function regenerateAllMatches(
        Larp $larp,
        SurveyResponseRepository $responseRepository
    ): Response {
        $responses = $responseRepository->findBy(['larp' => $larp]);
        $count = 0;

        foreach ($responses as $response) {
            $matchSuggestions = $this->matchingService->generateMatchSuggestions($response);
            $response->setMatchSuggestions($matchSuggestions);
            $count++;
        }

        $responseRepository->flush();

        $this->addFlash('success', $this->translator->trans('survey.response.all_matches_regenerated', [
            '%count%' => $count,
        ]));

        return $this->redirectToRoute('backoffice_larp_survey_responses_list', [
            'larp' => $larp->getId(),
        ]);
    }
}
