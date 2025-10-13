<?php

namespace App\Domain\Core\Entity;

use App\Domain\Account\Entity\User;
use App\Domain\Application\Entity\LarpApplication;
use App\Domain\Core\Entity\Enum\ParticipantRole;
use App\Domain\Core\Entity\Trait\UuidTraitEntity;
use App\Domain\Core\Repository\LarpParticipantRepository;
use App\Domain\StoryObject\Entity\Character;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entity joining User with Core and its Character
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

    #[ORM\OneToMany(targetEntity: Character::class, mappedBy: 'larpParticipant')]
    private ?Collection $larpCharacters = null;

    #[ORM\OneToOne(targetEntity: LarpApplication::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?LarpApplication $larpApplication = null;

    // Store an array of role strings (which correspond to ParticipantRole enum values)
    /** @see ParticipantRole */
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
     * @return ParticipantRole[]
     */
    public function getRoles(): array
    {
        return array_map(fn ($role) => ParticipantRole::from($role), $this->roles);
    }

    /**
     * Accepts an array of UserRole enum instances or valid role strings.
     *
     * @param ParticipantRole[] $roles
     */
    public function setRoles(array $roles): self
    {
        $this->roles = array_map(fn ($role) => $role instanceof ParticipantRole ? $role->value : $role, $roles);
        return $this;
    }

    public function isPlayer(): bool
    {
        return in_array(ParticipantRole::PLAYER, $this->getRoles());
    }

    public function isAdmin(): bool
    {
        return in_array(ParticipantRole::ORGANIZER, $this->getRoles());
    }

    public function isTrustPerson(): bool
    {
        return in_array(ParticipantRole::TRUST_PERSON, $this->getRoles());
    }

    public function isOrganizer(): bool
    {
        $organizerRoles = array_map(fn ($role) => $role->value, ParticipantRole::getOrganizers());
        $userRoles = array_map(fn ($role) => $role->value, $this->getRoles());

        return array_intersect($organizerRoles, $userRoles) !== [];
    }

    public function isStoryWriter(): bool
    {
        $storyWriters = array_map(fn ($role) => $role->value, ParticipantRole::getStoryWriters());
        $userRoles = array_map(fn ($role) => $role->value, $this->getRoles());

        return array_intersect($storyWriters, $userRoles) !== [];
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
     * @return Collection<int, Character>
     */
    public function getLarpCharacters(): Collection
    {
        return $this->larpCharacters;
    }

    public function addLarpCharacter(Character $larpCharacter): self
    {
        if (!$this->larpCharacters->contains($larpCharacter)) {
            $this->larpCharacters->add($larpCharacter);
            $larpCharacter->setLarpParticipant($this);
        }

        return $this;
    }

    public function removeLarpCharacter(Character $larpCharacter): self
    {
        // set the owning side to null (unless already changed)
        if ($this->larpCharacters->removeElement($larpCharacter) && $larpCharacter->getLarpParticipant() === $this) {
            $larpCharacter->setLarpParticipant(null);
        }

        return $this;
    }
}
