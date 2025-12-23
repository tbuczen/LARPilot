<?php

declare(strict_types=1);

namespace Tests\Support\Factory\Survey;

use App\Domain\Survey\Entity\Enum\SurveyQuestionType;
use App\Domain\Survey\Entity\SurveyQuestion;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<SurveyQuestion>
 */
final class SurveyQuestionFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return SurveyQuestion::class;
    }

    protected function defaults(): array
    {
        return [
            'questionText' => self::faker()->sentence() . '?',
            'helpText' => self::faker()->optional(0.3)->sentence(),
            'questionType' => SurveyQuestionType::TEXT,
            'isRequired' => true,
            'orderPosition' => 0,
            'tagCategory' => null,
            'survey' => SurveyFactory::new(),
        ];
    }

    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Larp $larp): void {})
        ;
    }

    /**
     * Question for a specific survey
     */
    public function forSurvey(mixed $survey): self
    {
        return $this->with(['survey' => $survey]);
    }

    /**
     * Short text question
     */
    public function text(): self
    {
        return $this->with(['questionType' => SurveyQuestionType::TEXT]);
    }

    /**
     * Long text question (textarea)
     */
    public function textarea(): self
    {
        return $this->with(['questionType' => SurveyQuestionType::TEXTAREA]);
    }

    /**
     * Single choice question with options
     */
    public function singleChoice(int $optionCount = 3): self
    {
        return $this->with(['questionType' => SurveyQuestionType::SINGLE_CHOICE])
            ->afterPersist(function (SurveyQuestion $question) use ($optionCount): void {
                for ($i = 0; $i < $optionCount; $i++) {
                    SurveyQuestionOptionFactory::new()
                        ->forQuestion($question)
                        ->with(['orderPosition' => $i])
                        ->create();
                }
            });
    }

    /**
     * Multiple choice question with options
     */
    public function multipleChoice(int $optionCount = 4): self
    {
        return $this->with(['questionType' => SurveyQuestionType::MULTIPLE_CHOICE])
            ->afterPersist(function (SurveyQuestion $question) use ($optionCount): void {
                for ($i = 0; $i < $optionCount; $i++) {
                    SurveyQuestionOptionFactory::new()
                        ->forQuestion($question)
                        ->with(['orderPosition' => $i])
                        ->create();
                }
            });
    }

    /**
     * Rating question (1-5 scale)
     */
    public function rating(): self
    {
        return $this->with([
            'questionType' => SurveyQuestionType::RATING,
            'questionText' => 'Rate from 1 to 5: ' . self::faker()->sentence(3),
        ]);
    }

    /**
     * Tag selection question
     */
    public function tagSelection(?string $category = null): self
    {
        return $this->with([
            'questionType' => SurveyQuestionType::TAG_SELECTION,
            'tagCategory' => $category,
        ]);
    }

    /**
     * Required question
     */
    public function required(): self
    {
        return $this->with(['isRequired' => true]);
    }

    /**
     * Optional question
     */
    public function optional(): self
    {
        return $this->with(['isRequired' => false]);
    }
}
