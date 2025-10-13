<?php

namespace App\Domain\Account\Entity;

use App\Domain\Account\Repository\UserSocialAccountRepository;
use App\Domain\Core\Entity\Trait\UuidTraitEntity;
use App\Domain\Integrations\Entity\Enum\SocialAccountProvider;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: UserSocialAccountRepository::class)]
class UserSocialAccount
{
    use UuidTraitEntity;
    use TimestampableEntity;

    #[ORM\Column(type: 'string', enumType: SocialAccountProvider::class)]
    private ?SocialAccountProvider $provider = null;

    #[ORM\Column(length: 255)]
    private ?string $providerUserId = null;

    #[ORM\Column(length: 255)]
    private ?string $displayName = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'socialAccounts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    public function getProvider(): ?SocialAccountProvider
    {
        return $this->provider;
    }

    public function setProvider(SocialAccountProvider $provider): self
    {
        $this->provider = $provider;
        return $this;
    }

    public function getProviderUserId(): ?string
    {
        return $this->providerUserId;
    }

    public function setProviderUserId(string $providerUserId): static
    {
        $this->providerUserId = $providerUserId;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(?string $displayName): void
    {
        $this->displayName = $displayName;
    }
}
