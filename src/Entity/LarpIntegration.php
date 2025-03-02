<?php

namespace App\Entity;

use App\Entity\Trait\CreatorAwareInterface;
use App\Entity\Trait\CreatorAwareTrait;
use App\Entity\Trait\UuidTraitEntity;
use App\Enum\LarpIntegrationProvider;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Timestampable;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
class LarpIntegration implements Timestampable, CreatorAwareInterface
{
    use UuidTraitEntity;
    use TimestampableEntity;
    use CreatorAwareTrait;

    #[ORM\Column(type: 'string')]
    private LarpIntegrationProvider $provider;

    #[ORM\Column(type: 'string')]
    private string $accessToken;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $refreshToken = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $expiresAt;

    #[ORM\ManyToOne(targetEntity: Larp::class, inversedBy: 'integrations')]
    #[ORM\JoinColumn(nullable: false)]
    private Larp $larp;

    public function getProvider(): LarpIntegrationProvider
    {
        return $this->provider;
    }

    public function setProvider(LarpIntegrationProvider $provider): void
    {
        $this->provider = $provider;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function setAccessToken(string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(?string $refreshToken): void
    {
        $this->refreshToken = $refreshToken;
    }

    public function getExpiresAt(): \DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeInterface $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
    }

    public function getLarp(): Larp
    {
        return $this->larp;
    }

    public function setLarp(Larp $larp): void
    {
        $this->larp = $larp;
    }


}
