<?php

declare(strict_types=1);

namespace Tests\Support\Factory\Core;

use App\Domain\Core\Entity\Enum\ParticipantRole;
use App\Domain\Core\Entity\LarpParticipant;
use Tests\Support\Factory\Account\UserFactory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<LarpParticipant>
 */
final class LarpParticipantFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return LarpParticipant::class;
    }

    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Larp $larp): void {})
            ;
    }

    protected function defaults(): array
    {
        return [
            'user' => UserFactory::new()->approved(),
            'larp' => LarpFactory::new(),
            'roles' => [ParticipantRole::PLAYER],
        ];
    }



    // ========================================================================
    // Factory States - Role Methods
    // ========================================================================

    /**
     * Participant with PLAYER role
     */
    public function player(): self
    {
        return $this->with([
            'roles' => [ParticipantRole::PLAYER],
        ]);
    }

    /**
     * Participant with ORGANIZER role (can manage LARP)
     */
    public function organizer(): self
    {
        return $this->with([
            'roles' => [ParticipantRole::ORGANIZER],
        ]);
    }

    /**
     * Participant with STAFF role
     */
    public function staff(): self
    {
        return $this->with([
            'roles' => [ParticipantRole::STAFF],
        ]);
    }

    /**
     * Participant with MAIN_STORY_WRITER role
     */
    public function mainStoryWriter(): self
    {
        return $this->with([
            'roles' => [ParticipantRole::MAIN_STORY_WRITER],
        ]);
    }

    /**
     * Participant with STORY_WRITER role
     */
    public function storyWriter(): self
    {
        return $this->with([
            'roles' => [ParticipantRole::STORY_WRITER],
        ]);
    }

    /**
     * Participant with PHOTOGRAPHER role
     */
    public function photographer(): self
    {
        return $this->with([
            'roles' => [ParticipantRole::PHOTOGRAPHER],
        ]);
    }

    /**
     * Participant with CRAFTER role
     */
    public function crafter(): self
    {
        return $this->with([
            'roles' => [ParticipantRole::CRAFTER],
        ]);
    }

    /**
     * Participant with MAKEUP_ARTIST role
     */
    public function makeupArtist(): self
    {
        return $this->with([
            'roles' => [ParticipantRole::MAKEUP_ARTIST],
        ]);
    }

    /**
     * Participant with GAME_MASTER role
     */
    public function gameMaster(): self
    {
        return $this->with([
            'roles' => [ParticipantRole::GAME_MASTER],
        ]);
    }

    /**
     * Participant with NPC_LONG role (long-duration NPC)
     */
    public function npcLong(): self
    {
        return $this->with([
            'roles' => [ParticipantRole::NPC_LONG],
        ]);
    }

    /**
     * Participant with NPC_SHORT role (short-duration NPC)
     */
    public function npcShort(): self
    {
        return $this->with([
            'roles' => [ParticipantRole::NPC_SHORT],
        ]);
    }

    /**
     * Participant with MEDIC role
     */
    public function medic(): self
    {
        return $this->with([
            'roles' => [ParticipantRole::MEDIC],
        ]);
    }

    /**
     * Participant with TRASHER role
     */
    public function trasher(): self
    {
        return $this->with([
            'roles' => [ParticipantRole::TRASHER],
        ]);
    }

    /**
     * Participant with TRUST_PERSON role (person of trust)
     */
    public function trustPerson(): self
    {
        return $this->with([
            'roles' => [ParticipantRole::TRUST_PERSON],
        ]);
    }

    /**
     * Participant with OUTFIT_APPROVER role
     */
    public function outfitApprover(): self
    {
        return $this->with([
            'roles' => [ParticipantRole::OUTFIT_APPROVER],
        ]);
    }

    /**
     * Participant with ACCOUNTANT role
     */
    public function accountant(): self
    {
        return $this->with([
            'roles' => [ParticipantRole::ACCOUNTANT],
        ]);
    }

    /**
     * Participant with GASTRONOMY role
     */
    public function gastronomy(): self
    {
        return $this->with([
            'roles' => [ParticipantRole::GASTRONOMY],
        ]);
    }

    // ========================================================================
    // Factory Configuration Methods
    // ========================================================================

    /**
     * Participant for a specific LARP
     */
    public function forLarp(mixed $larp): self
    {
        return $this->with([
            'larp' => $larp,
        ]);
    }

    /**
     * Participant for a specific User
     */
    public function forUser(mixed $user): self
    {
        return $this->with([
            'user' => $user,
        ]);
    }

    /**
     * Participant with multiple roles
     *
     * @param ParticipantRole[] $roles
     */
    public function withRoles(array $roles): self
    {
        return $this->with([
            'roles' => $roles,
        ]);
    }
}
