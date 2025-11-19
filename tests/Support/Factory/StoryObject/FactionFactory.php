<?php

declare(strict_types=1);

namespace Tests\Support\Factory\StoryObject;

use App\Domain\StoryObject\Entity\Faction;
use Tests\Support\Factory\Core\LarpFactory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Faction>
 */
final class FactionFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Faction::class;
    }

    protected function defaults(): array
    {
        return [
            'title' => self::faker()->company(),
            'description' => self::faker()->optional()->paragraphs(2, true),
            'larp' => LarpFactory::new(),
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
     * Faction for a specific LARP
     */
    public function forLarp(mixed $larp): self
    {
        return $this->with([
            'larp' => $larp,
        ]);
    }

    /**
     * Faction with specific title
     */
    public function withTitle(string $title): self
    {
        return $this->with([
            'title' => $title,
        ]);
    }

    /**
     * Faction with specific description
     */
    public function withDescription(string $description): self
    {
        return $this->with([
            'description' => $description,
        ]);
    }

    /**
     * Faction with member characters
     */
    public function withMembers(int $count = 3): self
    {
        return $this->afterPersist(function (Faction $faction) use ($count) {
            $characters = CharacterFactory::new()
                ->forLarp($faction->getLarp())
                ->many($count)
                ->create();

            foreach ($characters as $character) {
                $faction->addMember($character->_real());
            }
        });
    }
}
