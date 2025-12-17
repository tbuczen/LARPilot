<?php

declare(strict_types=1);

namespace Tests\Support\Factory\StoryObject;

use App\Domain\StoryObject\Entity\Thread;
use Tests\Support\Factory\Core\LarpFactory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Thread>
 */
final class ThreadFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Thread::class;
    }

    protected function defaults(): array
    {
        return [
            'title' => self::faker()->sentence(3),
            'description' => self::faker()->optional()->paragraphs(2, true),
            'larp' => LarpFactory::new(),
            'decisionTree' => null,
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
     * Thread for a specific LARP
     */
    public function forLarp(mixed $larp): self
    {
        return $this->with([
            'larp' => $larp,
        ]);
    }

    /**
     * Thread with specific title
     */
    public function withTitle(string $title): self
    {
        return $this->with([
            'title' => $title,
        ]);
    }

    /**
     * Thread with specific description
     */
    public function withDescription(string $description): self
    {
        return $this->with([
            'description' => $description,
        ]);
    }

    /**
     * Thread with involved characters
     */
    public function withInvolvedCharacters(int $count = 2): self
    {
        return $this->afterPersist(function (Thread $thread) use ($count) {
            $characters = CharacterFactory::new()
                ->forLarp($thread->getLarp())
                ->many($count)
                ->create();

            foreach ($characters as $character) {
                $thread->addInvolvedCharacter($character->_real());
            }
        });
    }

    /**
     * Thread with involved factions
     */
    public function withInvolvedFactions(int $count = 1): self
    {
        return $this->afterPersist(function (Thread $thread) use ($count) {
            $factions = FactionFactory::new()
                ->forLarp($thread->getLarp())
                ->many($count)
                ->create();

            foreach ($factions as $faction) {
                $thread->addInvolvedFaction($faction->_real());
            }
        });
    }

    /**
     * Thread with decision tree
     */
    public function withDecisionTree(array $decisionTree): self
    {
        return $this->with([
            'decisionTree' => $decisionTree,
        ]);
    }
}
