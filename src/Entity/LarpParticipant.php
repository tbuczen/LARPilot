<?php

namespace App\Entity;

use App\Entity\Enum\UserRole;
use App\Entity\LarpApplication;
use App\Entity\StoryObject\LarpCharacter;
use App\Entity\Trait\UuidTraitEntity;
use App\Repository\LarpParticipantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entity joining User with Larp and its Character
 */
#[ORM\Entity(repositoryClass: LarpParticipantRepository::class)]
#[ORM\Index(columns: ['user_id'])]
#[ORM\Index(columns: ['larp_id'])]
class LarpParticipant
{
    use UuidTraitEntity;
    use TimestampableEntity;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'larpParticipants')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Larp::class, inversedBy: 'larpParticipants')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Larp $larp = null;

    #[ORM\OneToMany(targetEntity: LarpCharacter::class, mappedBy: 'larpParticipant')]
    private ?Collection $larpCharacters = null;

    #[ORM\OneToOne(targetEntity: LarpApplication::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?LarpApplication $larpApplication = null;

    // Store an array of role strings (which correspond to UserRole enum values)
    /** @see UserRole */
    #[ORM\Column(type: Types::JSON, options: ['jsonb' => true])]
    private array $roles = [];

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->larpCharacters = new ArrayCollection();
    }

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

    public function getName(): ?string
    {
        return $this->user?->getUsername();
    }

    /**
     * Returns an array of UserRole enum instances.
     *
     * @return UserRole[]
     */
    public function getRoles(): array
    {
        return array_map(fn ($role) => UserRole::from($role), $this->roles);
    }

    /**
     * Accepts an array of UserRole enum instances or valid role strings.
     *
     * @param UserRole[] $roles
     */
    public function setRoles(array $roles): self
    {
        $this->roles = array_map(fn ($role) => $role instanceof UserRole ? $role->value : $role, $roles);
        return $this;
    }

    public function isPlayer(): bool
    {
        return in_array(UserRole::PLAYER, $this->getRoles());
    }

    public function isAdmin(): bool
    {
        return in_array(UserRole::ORGANIZER, $this->getRoles());
    }

    public function isTrustPerson(): bool
    {
        return in_array(UserRole::TRUST_PERSON, $this->getRoles());
    }

    public function isOrganizer(): bool
    {
        $organizerRoles = array_map(fn ($role) => $role->value, UserRole::getOrganizers());
        $userRoles = array_map(fn ($role) => $role->value, $this->getRoles());

        return !empty(array_intersect($organizerRoles, $userRoles));
    }

    public function isStoryWriter(): bool
    {
        $storyWriters = array_map(fn ($role) => $role->value, UserRole::getStoryWriters());
        $userRoles = array_map(fn ($role) => $role->value, $this->getRoles());

        return !empty(array_intersect($storyWriters, $userRoles));
    }

 

    public function getLarpApplication(): ?LarpApplication
    {
        return $this->larpApplication;
    }

    public function setLarpApplication(?LarpApplication $larpApplication): void
    {
        $this->larpApplication = $larpApplication;
    }

    /**
     * @return Collection<int, LarpCharacter>
     */
    public function getLarpCharacters(): Collection
    {
        return $this->larpCharacters;
    }

    public function addLarpCharacter(LarpCharacter $larpCharacter): self
    {
        if (!$this->larpCharacters->contains($larpCharacter)) {
            $this->larpCharacters->add($larpCharacter);
            $larpCharacter->setLarpParticipant($this);
        }

        return $this;
    }

    public function removeLarpCharacter(LarpCharacter $larpCharacter): self
    {
        if ($this->larpCharacters->removeElement($larpCharacter)) {
            // set the owning side to null (unless already changed)
            if ($larpCharacter->getLarpParticipant() === $this) {
                $larpCharacter->setLarpParticipant(null);
            }
        }

        return $this;
    }
}
