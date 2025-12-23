<?php

declare(strict_types=1);

namespace Tests\Support\Factory\StoryObject;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Enum\Gender;
use App\Domain\StoryObject\Entity\Character;
use App\Domain\StoryObject\Entity\Enum\CharacterType;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;

/**
 * @extends PersistentProxyObjectFactory<Character>
 */
final class CharacterFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Character::class;
    }

    protected function defaults(): array
    {
        return [
            'title' => self::faker()->name(),
            'description' => self::faker()->optional()->paragraph(),
            'larp' => LarpFactory::new(),
            'inGameName' => self::faker()->optional()->name(),
            'gender' => self::faker()->optional()->randomElement(Gender::cases()),
            'preferredGender' => self::faker()->optional()->randomElement(Gender::cases()),
            'characterType' => CharacterType::Player,
            'availableForRecruitment' => false,
            'notes' => self::faker()->optional()->paragraph(),
            'createdBy' => UserFactory::new(),
        ];
    }

    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Larp $larp): void {})
        ;
    }

    // ========================================================================
    // Factory States (Character Types)
    // ========================================================================

    /**
     * Player character
     */
    public function player(): self
    {
        return $this->with([
            'characterType' => CharacterType::Player,
        ]);
    }

    /**
     * Long-duration NPC character
     */
    public function longNpc(): self
    {
        return $this->with([
            'characterType' => CharacterType::LongNpc,
        ]);
    }

    /**
     * Short-duration NPC character
     */
    public function shortNpc(): self
    {
        return $this->with([
            'characterType' => CharacterType::ShortNpc,
        ]);
    }

    /**
     * Game Master character
     */
    public function gameMaster(): self
    {
        return $this->with([
            'characterType' => CharacterType::GameMaster,
        ]);
    }

    /**
     * Generic NPC character (e.g., raider, bandit, monk)
     */
    public function genericNpc(): self
    {
        return $this->with([
            'characterType' => CharacterType::GenericNpc,
        ]);
    }

    // ========================================================================
    // Factory Configuration Methods
    // ========================================================================

    /**
     * Character for a specific LARP
     */
    public function forLarp(mixed $larp): self
    {
        return $this->with([
            'larp' => $larp,
        ]);
    }

    /**
     * Character with specific gender
     */
    public function withGender(Gender $gender): self
    {
        return $this->with([
            'gender' => $gender,
            'preferredGender' => $gender,
        ]);
    }

    /**
     * Male character
     */
    public function male(): self
    {
        return $this->withGender(Gender::Male);
    }

    /**
     * Female character
     */
    public function female(): self
    {
        return $this->withGender(Gender::Female);
    }

    /**
     * Character with other gender
     */
    public function other(): self
    {
        return $this->withGender(Gender::Other);
    }

    /**
     * Character available for recruitment
     */
    public function availableForRecruitment(): self
    {
        return $this->with([
            'availableForRecruitment' => true,
        ]);
    }

    /**
     * Character with specific title
     */
    public function withTitle(string $title): self
    {
        return $this->with([
            'title' => $title,
        ]);
    }

    /**
     * Character with specific in-game name
     */
    public function withInGameName(string $inGameName): self
    {
        return $this->with([
            'inGameName' => $inGameName,
        ]);
    }

    public function withCreator(null|User|Proxy $user): self
    {
        if (null === $user) {
            $user = UserFactory::new();
        }
        return $this->with([
            'createdBy' => $user,
        ]);
    }
}
