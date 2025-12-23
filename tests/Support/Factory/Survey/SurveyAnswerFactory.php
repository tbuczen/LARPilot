<?php

declare(strict_types=1);

namespace Tests\Support\Factory\Survey;

use App\Domain\Survey\Entity\SurveyAnswer;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<SurveyAnswer>
 */
final class SurveyAnswerFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return SurveyAnswer::class;
    }

    protected function defaults(): array
    {
        return [
            'answerText' => null,
            'response' => SurveyResponseFactory::new(),
            'question' => SurveyQuestionFactory::new(),
        ];
    }

    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Larp $larp): void {})
        ;
    }

    /**
     * Answer for a specific question
     */
    public function forQuestion(mixed $question): self
    {
        return $this->with(['question' => $question]);
    }

    /**
     * Answer for a specific response
     */
    public function forResponse(mixed $response): self
    {
        return $this->with(['response' => $response]);
    }

    /**
     * Text answer
     */
    public function textAnswer(?string $text = null): self
    {
        return $this->with([
            'answerText' => $text ?? self::faker()->sentence(),
        ]);
    }

    /**
     * Rating answer (1-5)
     */
    public function ratingAnswer(int $rating): self
    {
        if ($rating < 1 || $rating > 5) {
            throw new \InvalidArgumentException('Rating must be between 1 and 5');
        }

        return $this->with([
            'answerText' => (string) $rating,
        ]);
    }

    /**
     * Single choice answer with specific option
     */
    public function singleChoiceAnswer(mixed $option): self
    {
        return $this->afterPersist(function (SurveyAnswer $answer) use ($option): void {
            $answer->addSelectedOption($option);
        });
    }

    /**
     * Multiple choice answer with specific options
     */
    public function multipleChoiceAnswer(array $options): self
    {
        return $this->afterPersist(function (SurveyAnswer $answer) use ($options): void {
            foreach ($options as $option) {
                $answer->addSelectedOption($option);
            }
        });
    }

    /**
     * Tag selection answer with specific tags
     */
    public function tagSelectionAnswer(array $tags): self
    {
        return $this->afterPersist(function (SurveyAnswer $answer) use ($tags): void {
            foreach ($tags as $tag) {
                $answer->addSelectedTag($tag);
            }
        });
    }
}
