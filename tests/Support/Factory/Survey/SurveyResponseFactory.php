<?php

declare(strict_types=1);

namespace Tests\Support\Factory\Survey;

use App\Domain\Application\Entity\Enum\SubmissionStatus;
use App\Domain\Core\Entity\Enum\LarpStageStatus;
use App\Domain\Survey\Entity\SurveyResponse;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<SurveyResponse>
 */
final class SurveyResponseFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return SurveyResponse::class;
    }

    protected function defaults(): array
    {
        return [
            'status' => SubmissionStatus::NEW,
            'matchSuggestions' => null,
            'assignedCharacter' => null,
            'organizerNotes' => null,
            'survey' => SurveyFactory::new(),
            'larp' => LarpFactory::new(),
            'user' => UserFactory::new()->approved(),
        ];
    }

    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Larp $larp): void {})
        ;
    }

    /**
     * Response for a specific survey
     */
    public function forSurvey(mixed $survey): self
    {
        return $this->with(['survey' => $survey]);
    }

    /**
     * Response for a specific LARP
     */
    public function forLarp(mixed $larp): self
    {
        return $this->with(['larp' => $larp]);
    }

    /**
     * Response by a specific user
     */
    public function forUser(mixed $user): self
    {
        return $this->with(['user' => $user]);
    }

    /**
     * Pending response (NEW status, not assigned)
     */
    public function pending(): self
    {
        return $this->with([
            'status' => SubmissionStatus::NEW,
            'assignedCharacter' => null,
        ]);
    }

    /**
     * Assigned response (with character)
     */
    public function assigned(mixed $character = null): self
    {
        return $this->with([
            'status' => SubmissionStatus::ASSIGNED,
            'assignedCharacter' => $character,
        ]);
    }

    /**
     * Response with specific status
     */
    public function withStatus(LarpStageStatus $status): self
    {
        return $this->with(['status' => $status]);
    }

    /**
     * Response with match suggestions
     */
    public function withMatchSuggestions(array $suggestions): self
    {
        return $this->with(['matchSuggestions' => $suggestions]);
    }

    /**
     * Response with organizer notes
     */
    public function withNotes(string $notes): self
    {
        return $this->with(['organizerNotes' => $notes]);
    }

    /**
     * Response with answers to all survey questions
     */
    public function withAnswers(): self
    {
        return $this->afterPersist(function (SurveyResponse $response): void {
            $survey = $response->getSurvey();
            if ($survey) {
                foreach ($survey->getQuestions() as $question) {
                    $answer = SurveyAnswerFactory::new()
                        ->forResponse($response)
                        ->forQuestion($question);

                    // Provide appropriate answer based on question type
                    match ($question->getQuestionType()) {
                        SurveyQuestionType::TEXT => $answer->textAnswer(),
                        SurveyQuestionType::TEXTAREA => $answer->textAnswer(self::faker()->paragraph()),
                        SurveyQuestionType::RATING => $answer->ratingAnswer(self::faker()->numberBetween(1, 5)),
                        default => $answer,
                    };

                    $answer->create();
                }
            }
        });
    }
}
