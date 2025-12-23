<?php

declare(strict_types=1);

namespace App\Domain\Survey\Service;

use App\Domain\Core\Entity\Larp;
use App\Domain\Survey\Entity\Enum\SurveyQuestionType;
use App\Domain\Survey\Entity\Survey;
use App\Domain\Survey\Entity\SurveyQuestion;
use App\Domain\Survey\Entity\SurveyQuestionOption;

class SurveyTemplateService
{
    /**
     * Create a new survey with pre-populated template questions.
     */
    public function createSurveyFromTemplate(Larp $larp): Survey
    {
        $survey = new Survey();
        $survey->setLarp($larp);
        $survey->setTitle('LARP Application Survey');
        $survey->setDescription('This survey helps us match you with the perfect character for this LARP.');
        $survey->setIsActive(false);

        $this->addTemplateQuestions($survey);

        return $survey;
    }

    /**
     * Add all template questions to a survey.
     */
    private function addTemplateQuestions(Survey $survey): void
    {
        $position = 1;

        // Question 1: Favourite playstyle
        $question = new SurveyQuestion();
        $question->setQuestionText('What is your favourite playstyle?');
        $question->setHelpText('Describe the type of roleplay you enjoy most (e.g., political intrigue, combat, investigation, social interaction, etc.)');
        $question->setQuestionType(SurveyQuestionType::TEXTAREA);
        $question->setIsRequired(false);
        $question->setOrderPosition($position++);
        $survey->addQuestion($question);

        // Question 2: LARP experience level
        $question = new SurveyQuestion();
        $question->setQuestionText('What is your LARP experience level?');
        $question->setQuestionType(SurveyQuestionType::SINGLE_CHOICE);
        $question->setIsRequired(true);
        $question->setOrderPosition($position++);
        $this->addExperienceLevelOptions($question);
        $survey->addQuestion($question);

        // Question 3: Preferred tags
        $question = new SurveyQuestion();
        $question->setQuestionText('What themes or elements are you most interested in?');
        $question->setHelpText('Select the tags that interest you most');
        $question->setQuestionType(SurveyQuestionType::TAG_SELECTION);
        $question->setIsRequired(false);
        $question->setOrderPosition($position++);
        $survey->addQuestion($question);

        // Question 4: Unwanted tags
        $question = new SurveyQuestion();
        $question->setQuestionText('Are there any themes or elements you want to avoid?');
        $question->setHelpText('Select tags for content you prefer not to encounter');
        $question->setQuestionType(SurveyQuestionType::TAG_SELECTION);
        $question->setIsRequired(false);
        $question->setOrderPosition($position++);
        $survey->addQuestion($question);

        // Question 5: Triggers/safety concerns
        $question = new SurveyQuestion();
        $question->setQuestionText('Do you have any triggers or safety concerns?');
        $question->setHelpText('Please list any content or situations you need to avoid for safety or comfort reasons');
        $question->setQuestionType(SurveyQuestionType::TEXTAREA);
        $question->setIsRequired(false);
        $question->setOrderPosition($position++);
        $survey->addQuestion($question);

        // Question 6: Preferred character gender
        $question = new SurveyQuestion();
        $question->setQuestionText('Do you have a preferred character gender?');
        $question->setQuestionType(SurveyQuestionType::SINGLE_CHOICE);
        $question->setIsRequired(false);
        $question->setOrderPosition($position++);
        $this->addGenderPreferenceOptions($question);
        $survey->addQuestion($question);

        // Question 7: Character complexity preference
        $question = new SurveyQuestion();
        $question->setQuestionText('How complex do you want your character to be?');
        $question->setHelpText('1 = Simple and straightforward, 5 = Very complex with many connections');
        $question->setQuestionType(SurveyQuestionType::RATING);
        $question->setIsRequired(false);
        $question->setOrderPosition($position++);
        $survey->addQuestion($question);

        // Question 8: Costume and prop skills
        $question = new SurveyQuestion();
        $question->setQuestionText('What are your costume and prop creation skills?');
        $question->setQuestionType(SurveyQuestionType::MULTIPLE_CHOICE);
        $question->setIsRequired(false);
        $question->setOrderPosition($position++);
        $this->addCostumeSkillOptions($question);
        $survey->addQuestion($question);

        // Question 9: Dietary restrictions
        $question = new SurveyQuestion();
        $question->setQuestionText('Do you have any dietary restrictions?');
        $question->setHelpText('Please list any allergies, dietary requirements, or food preferences');
        $question->setQuestionType(SurveyQuestionType::TEXT);
        $question->setIsRequired(false);
        $question->setOrderPosition($position++);
        $survey->addQuestion($question);

        // Question 10: Accessibility needs
        $question = new SurveyQuestion();
        $question->setQuestionText('Do you have any accessibility needs?');
        $question->setHelpText('Please describe any accommodations you may need (mobility, sensory, medical, etc.)');
        $question->setQuestionType(SurveyQuestionType::TEXTAREA);
        $question->setIsRequired(false);
        $question->setOrderPosition($position++);
        $survey->addQuestion($question);
    }

    private function addExperienceLevelOptions(SurveyQuestion $question): void
    {
        $options = [
            'This is my first LARP',
            'I have attended 1-3 LARPs',
            'I have attended 4-10 LARPs',
            'I have attended more than 10 LARPs',
            'I am an experienced LARPer (20+ games)',
        ];

        foreach ($options as $index => $optionText) {
            $option = new SurveyQuestionOption();
            $option->setOptionText($optionText);
            $option->setOrderPosition($index + 1);
            $question->addOption($option);
        }
    }

    private function addGenderPreferenceOptions(SurveyQuestion $question): void
    {
        $options = [
            'Male character',
            'Female character',
            'Non-binary character',
            'No preference',
        ];

        foreach ($options as $index => $optionText) {
            $option = new SurveyQuestionOption();
            $option->setOptionText($optionText);
            $option->setOrderPosition($index + 1);
            $question->addOption($option);
        }
    }

    private function addCostumeSkillOptions(SurveyQuestion $question): void
    {
        $options = [
            'I can create my own costume',
            'I can modify existing clothing',
            'I can create simple props',
            'I can create complex props',
            'I can do makeup/prosthetics',
            'I need help with costume',
        ];

        foreach ($options as $index => $optionText) {
            $option = new SurveyQuestionOption();
            $option->setOptionText($optionText);
            $option->setOrderPosition($index + 1);
            $question->addOption($option);
        }
    }
}
