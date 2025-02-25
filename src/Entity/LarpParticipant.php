<?php

namespace App\Entity;

use App\Entity\Trait\UuidTraitEntity;
use App\Enum\UserRole;
use App\Repository\LarpParticipantRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

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

    public function setLarp(Larp $larp): self
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
}
