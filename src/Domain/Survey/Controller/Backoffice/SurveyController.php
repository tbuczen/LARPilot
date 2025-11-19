<?php

declare(strict_types=1);

namespace App\Domain\Survey\Controller\Backoffice;

use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Entity\Larp;
use App\Domain\Survey\Entity\Survey;
use App\Domain\Survey\Form\SurveyType;
use App\Domain\Survey\Repository\SurveyRepository;
use App\Domain\Survey\Service\SurveyTemplateService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}/survey', name: 'backoffice_larp_survey_')]
class SurveyController extends BaseController
{
    public function __construct(
        private readonly SurveyTemplateService $templateService
    ) {
    }

    #[Route('', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Larp $larp,
        SurveyRepository $surveyRepository
    ): Response {
        // Get or create survey for this LARP
        $survey = $larp->getSurvey();

        if (!$survey instanceof Survey) {
            // Create from template if doesn't exist
            $survey = $this->templateService->createSurveyFromTemplate($larp);
        }

        $form = $this->createForm(SurveyType::class, $survey);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Survey $survey */
            $survey = $form->getData();

            // Update question order positions
            $position = 1;
            foreach ($survey->getQuestions() as $question) {
                $question->setOrderPosition($position++);

                // Update option order positions
                $optionPosition = 1;
                foreach ($question->getOptions() as $option) {
                    $option->setOrderPosition($optionPosition++);
                }
            }

            $surveyRepository->save($survey);
            $this->addFlash('success', $this->translator->trans('survey.saved'));

            return $this->redirectToRoute('backoffice_larp_survey_edit', ['larp' => $larp->getId()]);
        }

        return $this->render('backoffice/survey/edit.html.twig', [
            'form' => $form->createView(),
            'larp' => $larp,
            'survey' => $survey,
        ]);
    }

    #[Route('/preview', name: 'preview', methods: ['GET'])]
    public function preview(Larp $larp): Response
    {
        $survey = $larp->getSurvey();

        if (!$survey instanceof Survey) {
            $this->addFlash('error', $this->translator->trans('survey.not_found'));

            return $this->redirectToRoute('backoffice_larp_survey_edit', ['larp' => $larp->getId()]);
        }

        return $this->render('backoffice/survey/preview.html.twig', [
            'larp' => $larp,
            'survey' => $survey,
        ]);
    }
}
