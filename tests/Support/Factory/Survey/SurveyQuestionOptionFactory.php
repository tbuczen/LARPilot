<?php

declare(strict_types=1);

namespace Tests\Support\Factory\Survey;

use App\Domain\Survey\Entity\SurveyQuestionOption;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<SurveyQuestionOption>
 */
final class SurveyQuestionOptionFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return SurveyQuestionOption::class;
    }

    protected function defaults(): array
    {
        return [
            'optionText' => self::faker()->words(3, true),
            'orderPosition' => 0,
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
     * Option for a specific question
     */
    public function forQuestion(mixed $question): self
    {
        return $this->with(['question' => $question]);
    }
}
