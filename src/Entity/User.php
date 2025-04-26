<?php

namespace App\Entity;

use App\Entity\Trait\UuidTraitEntity;
use App\Enum\Locale;
use App\Repository\UserRepository;
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

    /** @var Collection<LarpCharacterSubmission> */
    #[ORM\OneToMany(targetEntity: LarpCharacterSubmission::class, mappedBy: 'user')]
    private Collection $submissions;

    #[ORM\Column(nullable: true, enumType: Locale::class)]
    private ?Locale $preferredLocale = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /** @var Collection<LarpParticipant>  */
    #[ORM\OneToMany(targetEntity: LarpParticipant::class, mappedBy: 'user')]
    private Collection $larpParticipants;

    /** @var Collection<UserSocialAccount>  */
    #[ORM\OneToMany(targetEntity: UserSocialAccount::class, mappedBy: 'user')]
    private Collection $socialAccounts;
    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->submissions = new ArrayCollection();
        $this->larpParticipants = new ArrayCollection();
        $this->socialAccounts = new ArrayCollection();
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
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

    public function setContactEmail(string $contactEmail): void
    {
        $this->contactEmail = $contactEmail;
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
}
