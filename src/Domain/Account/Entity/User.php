<?php

namespace App\Domain\Account\Entity;

use App\Domain\Account\Entity\Enum\UserStatus;
use App\Domain\Account\Repository\UserRepository;
use App\Domain\Application\Entity\LarpApplication;
use App\Domain\Core\Entity\Enum\Locale;
use App\Domain\Core\Entity\LarpParticipant;
use App\Domain\Core\Entity\Trait\UuidTraitEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_USERNAME', fields: ['username'])]
class User implements UserInterface
{
    use UuidTraitEntity;
    use TimestampableEntity;

    #[ORM\Column(length: 180)]
    private ?string $username = null;

    #[ORM\Column(length: 180)]
    private ?string $contactEmail = null;

    /** @var Collection<LarpApplication> */
    #[ORM\OneToMany(targetEntity: LarpApplication::class, mappedBy: 'user')]
    private Collection $applications;

    #[ORM\Column(nullable: true, enumType: Locale::class)]
    private ?Locale $preferredLocale = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column(enumType: UserStatus::class)]
    private UserStatus $status = UserStatus::PENDING;

    #[ORM\ManyToOne(targetEntity: Plan::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Plan $plan = null;

    /** @var Collection<LarpParticipant>  */
    #[ORM\OneToMany(targetEntity: LarpParticipant::class, mappedBy: 'user')]
    private Collection $larpParticipants;

    /** @var Collection<UserSocialAccount>  */
    #[ORM\OneToMany(targetEntity: UserSocialAccount::class, mappedBy: 'user')]
    private Collection $socialAccounts;
    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->applications = new ArrayCollection();
        $this->larpParticipants = new ArrayCollection();
        $this->socialAccounts = new ArrayCollection();
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string)$this->username;
    }

    /**
     * @return list<string>
     * @see UserInterface
     *
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(string $contactEmail): self
    {
        $this->contactEmail = $contactEmail;

        return $this;
    }

    public function getPreferredLocale(): ?Locale
    {
        return $this->preferredLocale;
    }

    public function setPreferredLocale(?Locale $preferredLocale): self
    {
        $this->preferredLocale = $preferredLocale;
        return $this;
    }

    public function getStatus(): UserStatus
    {
        return $this->status;
    }

    public function setStatus(UserStatus $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function isApproved(): bool
    {
        return $this->status === UserStatus::APPROVED;
    }

    public function isPending(): bool
    {
        return $this->status === UserStatus::PENDING;
    }

    public function isSuspended(): bool
    {
        return $this->status === UserStatus::SUSPENDED;
    }

    public function isBanned(): bool
    {
        return $this->status === UserStatus::BANNED;
    }

    public function getPlan(): ?Plan
    {
        return $this->plan;
    }

    public function setPlan(?Plan $plan): self
    {
        $this->plan = $plan;
        return $this;
    }

    /**
     * Check if user can create more LARPs based on their plan.
     */
    public function canCreateMoreLarps(int $currentLarpCount): bool
    {
        // If no plan assigned, use free tier default (1 LARP)
        if ($this->plan === null) {
            return $currentLarpCount < 1;
        }

        // Check plan limit
        $maxLarps = $this->plan->getMaxLarps();

        // NULL = unlimited
        if ($maxLarps === null) {
            return true;
        }

        return $currentLarpCount < $maxLarps;
    }

    /**
     * Get remaining LARP slots based on current plan.
     */
    public function getRemainingLarpSlots(int $currentLarpCount): ?int
    {
        // If no plan, use free tier default
        if ($this->plan === null) {
            return max(0, 1 - $currentLarpCount);
        }

        $maxLarps = $this->plan->getMaxLarps();

        // NULL = unlimited
        if ($maxLarps === null) {
            return null;
        }

        return max(0, $maxLarps - $currentLarpCount);
    }

    /**
     * Get the max LARPs allowed for this user's plan.
     */
    public function getMaxLarpsAllowed(): ?int
    {
        if ($this->plan === null) {
            return 1; // Free tier default
        }

        return $this->plan->getMaxLarps();
    }

    /**
     * @return Collection<LarpParticipant>
     */
    public function getLarpParticipants(): Collection
    {
        return $this->larpParticipants;
    }

    /**
     * Get count of LARPs where user is an organizer.
     */
    public function getOrganizerLarpCount(): int
    {
        return $this->larpParticipants->filter(
            fn(LarpParticipant $participant) => $participant->isOrganizer()
        )->count();
    }
}
