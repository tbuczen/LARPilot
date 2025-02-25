<?php

namespace App\Entity;

use App\Entity\Trait\UuidTraitEntity;
use App\Enum\SocialAccountProvider;
use App\Repository\SocialAccountRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SocialAccountRepository::class)]
class UserSocialAccount
{
    use UuidTraitEntity;

    #[ORM\Column(type: 'string', enumType: SocialAccountProvider::class)]
    private ?SocialAccountProvider $provider = null;

    #[ORM\Column(length: 255)]
    private ?string $providerUserId = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

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
}
