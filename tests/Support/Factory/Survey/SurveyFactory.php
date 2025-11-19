<?php

declare(strict_types=1);

namespace Tests\Support\Factory\Survey;

use App\Domain\Survey\Entity\Survey;
use Tests\Support\Factory\Core\LarpFactory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Survey>
 */
final class SurveyFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Survey::class;
    }

    protected function defaults(): array
    {
        return [
            'title' => self::faker()->sentence(4),
            'description' => self::faker()->paragraph(),
            'isActive' => true,
            'larp' => LarpFactory::new(),
        ];
    }

    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Larp $larp): void {})
            ;
    }

    /**
     * Active survey (accepting responses)
     */
    public function active(): self
    {
        return $this->with(['isActive' => true]);
    }

    /**
     * Inactive survey (not accepting responses)
     */
    public function inactive(): self
    {
        return $this->with(['isActive' => false]);
    }

    /**
     * Survey for a specific LARP
     */
    public function forLarp(mixed $larp): self
    {
        return $this->with(['larp' => $larp]);
    }

    /**
     * Survey with questions
     */
    public function withQuestions(int $count = 3): self
    {
        return $this->afterPersist(function (Survey $survey) use ($count): void {
            for ($i = 0; $i < $count; $i++) {
                SurveyQuestionFactory::new()
                    ->forSurvey($survey)
                    ->with(['orderPosition' => $i])
                    ->create();
            }
        });
    }
}
