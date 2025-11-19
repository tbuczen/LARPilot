<?php

declare(strict_types=1);

namespace Tests\Support\Factory\Application;

use App\Domain\Application\Entity\LarpApplicationChoice;
use Tests\Support\Factory\StoryObject\CharacterFactory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<LarpApplicationChoice>
 */
final class LarpApplicationChoiceFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return LarpApplicationChoice::class;
    }

    protected function defaults(): array
    {
        return [
            'application' => LarpApplicationFactory::new(),
            'character' => CharacterFactory::new(),
            'priority' => self::faker()->numberBetween(1, 10),
            'justification' => self::faker()->optional()->paragraph(),
            'visual' => self::faker()->optional()->paragraph(),
            'votes' => 0,
        ];
    }

    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Larp $larp): void {})
            ;
    }

    // ========================================================================
    // Factory Configuration Methods
    // ========================================================================

    /**
     * Choice for a specific application
     */
    public function forApplication(mixed $application): self
    {
        return $this->with([
            'application' => $application,
        ]);
    }

    /**
     * Choice for a specific character
     */
    public function forCharacter(mixed $character): self
    {
        return $this->with([
            'character' => $character,
        ]);
    }

    /**
     * Choice with specific priority (1 = highest)
     */
    public function withPriority(int $priority): self
    {
        return $this->with([
            'priority' => $priority,
        ]);
    }

    /**
     * Top priority choice (priority = 1)
     */
    public function topPriority(): self
    {
        return $this->withPriority(1);
    }

    /**
     * Choice with specific number of votes
     */
    public function withVotes(int $votes): self
    {
        return $this->with([
            'votes' => $votes,
        ]);
    }

    /**
     * Choice with justification
     */
    public function withJustification(string $justification): self
    {
        return $this->with([
            'justification' => $justification,
        ]);
    }

    /**
     * Choice with visual description
     */
    public function withVisual(string $visual): self
    {
        return $this->with([
            'visual' => $visual,
        ]);
    }
}
