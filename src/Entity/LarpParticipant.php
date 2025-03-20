<?php

namespace App\Entity;

use App\Entity\Trait\UuidTraitEntity;
use App\Enum\UserRole;
use App\Repository\LarpParticipantRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * Entity joining User with Larp and its Character
 */
#[ORM\Entity(repositoryClass: LarpParticipantRepository::class)]
class LarpParticipant
{
    use UuidTraitEntity;
    use TimestampableEntity;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'larpParticipants')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Larp::class, inversedBy: 'larpParticipants')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Larp $larp = null;

    #[ORM\ManyToOne(targetEntity: LarpCharacter::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?LarpCharacter $larpCharacter = null;

    // Store an array of role strings (which correspond to UserRole enum values)
    /** @see UserRole  */
    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\ManyToOne(targetEntity: LarpFaction::class, inversedBy: 'participants')]
    private ?LarpFaction $faction = null;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getLarp(): ?Larp
    {
        return $this->larp;
    }

    public function setLarp(?Larp $larp): self
    {
        $this->larp = $larp;
        return $this;
    }

    /**
     * Returns an array of UserRole enum instances.
     *
     * @return UserRole[]
     */
    public function getRoles(): array
    {
        return array_map(fn($role) => UserRole::from($role), $this->roles);
    }

    /**
     * Accepts an array of UserRole enum instances or valid role strings.
     *
     * @param UserRole[] $roles
     */
    public function setRoles(array $roles): self
    {
        $this->roles = array_map(fn($role) => $role instanceof UserRole ? $role->value : $role, $roles);
        return $this;
    }

    public function getFaction(): ?LarpFaction
    {
        return $this->faction;
    }

    public function setFaction(?LarpFaction $faction): self
    {
        $this->faction = $faction;
        return $this;
    }

    public function isPlayer(): bool
    {
        return in_array(UserRole::PLAYER,$this->getRoles());
    }

    public function isAdmin(): bool
    {
        return in_array(UserRole::ORGANIZER,$this->getRoles());
    }

    public function isTrustPerson(): bool
    {
        return in_array(UserRole::TRUST_PERSON,$this->getRoles());
    }

    public function isOrganizer(): bool
    {
        $organizerRoles = array_map(fn($role) => $role->value, UserRole::getOrganizers());
        $userRoles = array_map(fn($role) => $role->value, $this->getRoles());

        return !empty(array_intersect($organizerRoles, $userRoles));
    }

    public function isStoryWriter(): bool
    {
        $storyWriters = array_map(fn($role) => $role->value, UserRole::getStoryWriters());
        $userRoles = array_map(fn($role) => $role->value, $this->getRoles());

        return !empty(array_intersect($storyWriters, $userRoles));
    }

    public function getLarpCharacter(): ?LarpCharacter
    {
        return $this->larpCharacter;
    }

    public function setLarpCharacter(?LarpCharacter $larpCharacter): void
    {
        $this->larpCharacter = $larpCharacter;
    }
}
