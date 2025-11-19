<?php

declare(strict_types=1);

namespace App\Domain\Survey\Controller\Public;

use App\Domain\Application\Entity\Enum\SubmissionStatus;
use App\Domain\Core\Entity\Larp;
use App\Domain\Survey\Entity\Enum\SurveyQuestionType;
use App\Domain\Survey\Entity\Survey;
use App\Domain\Survey\Entity\SurveyAnswer;
use App\Domain\Survey\Entity\SurveyResponse;
use App\Domain\Survey\Repository\SurveyResponseRepository;
use App\Domain\Survey\Service\CharacterMatchingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/larp/{slug}/survey', name: 'public_larp_survey_')]
class SurveySubmissionController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly CharacterMatchingService $matchingService
    ) {
    }

    #[Route('', name: 'show', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function show(
        Request $request,
        Larp $larp,
        FormFactoryInterface $formFactory,
        SurveyResponseRepository $responseRepository
    ): Response {
        // Check LARP is in INQUIRIES status
        if ($larp->getStatus()?->value !== 'INQUIRIES') {
            $this->addFlash('error', $this->translator->trans('survey.not_accepting_responses'));

            return $this->redirectToRoute('public_larp_details', ['slug' => $larp->getSlug()]);
        }

        // Check application mode is SURVEY
        if ($larp->getApplicationMode()->value !== 'survey') {
            $this->addFlash('error', $this->translator->trans('survey.not_in_survey_mode'));

            return $this->redirectToRoute('public_larp_details', ['slug' => $larp->getSlug()]);
        }

        // Get survey
        $survey = $larp->getSurvey();
        if (!$survey instanceof Survey || !$survey->isActive()) {
            $this->addFlash('error', $this->translator->trans('survey.not_available'));

            return $this->redirectToRoute('public_larp_details', ['slug' => $larp->getSlug()]);
        }

        // Check for existing response
        $existingResponse = $responseRepository->findOneBy([
            'larp' => $larp,
            'user' => $this->getUser(),
        ]);

        if ($existingResponse instanceof SurveyResponse) {
            $this->addFlash('info', $this->translator->trans('survey.already_submitted'));

            return $this->redirectToRoute('public_larp_details', ['slug' => $larp->getSlug()]);
        }

        // Build dynamic form from survey
        $form = $this->buildSurveyForm($formFactory, $survey);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();

            // Create survey response
            $response = new SurveyResponse();
            $response->setSurvey($survey);
            $response->setLarp($larp);
            $response->setUser($this->getUser());
            $response->setStatus(SubmissionStatus::NEW);

            // Process answers
            foreach ($survey->getQuestions() as $question) {
                $fieldName = 'question_' . $question->getId()->toRfc4122();
                $answerValue = $formData[$fieldName] ?? null;

                if ($answerValue !== null && $answerValue !== '' && $answerValue !== []) {
                    $answer = new SurveyAnswer();
                    $answer->setQuestion($question);
                    $answer->setResponse($response);

                    // Handle different question types
                    if ($question->getQuestionType() === SurveyQuestionType::TAG_SELECTION) {
                        // $answerValue is array of Tag entities
                        foreach ($answerValue as $tag) {
                            $answer->addSelectedTag($tag);
                        }
                    } elseif ($question->getQuestionType() === SurveyQuestionType::SINGLE_CHOICE) {
                        // $answerValue is SurveyQuestionOption entity
                        $answer->addSelectedOption($answerValue);
                    } elseif ($question->getQuestionType() === SurveyQuestionType::MULTIPLE_CHOICE) {
                        // $answerValue is array of SurveyQuestionOption entities
                        foreach ($answerValue as $option) {
                            $answer->addSelectedOption($option);
                        }
                    } else {
                        // TEXT, TEXTAREA, RATING
                        $answer->setAnswerText((string) $answerValue);
                    }

                    $response->addAnswer($answer);
                }
            }

            // Generate initial match suggestions
            $matchSuggestions = $this->matchingService->generateMatchSuggestions($response);
            $response->setMatchSuggestions($matchSuggestions);

            // Save response
            $responseRepository->save($response);

            $this->addFlash('success', $this->translator->trans('survey.submitted_successfully'));

            return $this->redirectToRoute('public_larp_details', ['slug' => $larp->getSlug()]);
        }

        return $this->render('public/survey/submit.html.twig', [
            'larp' => $larp,
            'survey' => $survey,
            'form' => $form->createView(),
        ]);
    }

    private function buildSurveyForm(FormFactoryInterface $formFactory, Survey $survey): \Symfony\Component\Form\FormInterface
    {
        $builder = $formFactory->createBuilder();

        foreach ($survey->getQuestions() as $question) {
            $fieldName = 'question_' . $question->getId()->toRfc4122();
            $options = [
                'label' => $question->getQuestionText(),
                'required' => $question->isRequired(),
                'help' => $question->getHelpText(),
                'attr' => ['class' => 'form-control'],
            ];

            switch ($question->getQuestionType()) {
                case SurveyQuestionType::TEXT:
                    $builder->add($fieldName, TextType::class, $options);
                    break;

                case SurveyQuestionType::TEXTAREA:
                    $options['attr']['rows'] = 4;
                    $builder->add($fieldName, TextareaType::class, $options);
                    break;

                case SurveyQuestionType::SINGLE_CHOICE:
                    $options['choices'] = [];
                    foreach ($question->getOptions() as $option) {
                        $options['choices'][$option->getOptionText()] = $option;
                    }
                    $options['expanded'] = true;
                    $options['attr'] = ['class' => 'form-check'];
                    $builder->add($fieldName, ChoiceType::class, $options);
                    break;

                case SurveyQuestionType::MULTIPLE_CHOICE:
                    $options['choices'] = [];
                    foreach ($question->getOptions() as $option) {
                        $options['choices'][$option->getOptionText()] = $option;
                    }
                    $options['multiple'] = true;
                    $options['expanded'] = true;
                    $options['attr'] = ['class' => 'form-check'];
                    $builder->add($fieldName, ChoiceType::class, $options);
                    break;

                case SurveyQuestionType::RATING:
                    $options['attr']['min'] = 1;
                    $options['attr']['max'] = 5;
                    $builder->add($fieldName, IntegerType::class, $options);
                    break;

                case SurveyQuestionType::TAG_SELECTION:
                    // Use EntityType for tags
                    $options['class'] = \App\Domain\Core\Entity\Tag::class;
                    $options['query_builder'] = fn ($repo) => $repo->createQueryBuilder('t')
                        ->where('t.larp = :larp')
                        ->setParameter('larp', $survey->getLarp())
                        ->orderBy('t.title', 'ASC');
                    $options['choice_label'] = 'title';
                    $options['multiple'] = true;
                    $options['autocomplete'] = true;
                    $options['attr'] = ['class' => 'form-select'];
                    $builder->add($fieldName, \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, $options);
                    break;
            }
        }

        $builder->add('submit', SubmitType::class, [
            'label' => 'survey.submit',
            'attr' => ['class' => 'btn btn-success btn-lg'],
        ]);

        return $builder->getForm();
    }
}
