<?php

namespace App\Entity;

use App\Entity\Enum\UserRole;
use App\Entity\StoryObject\LarpCharacter;
use App\Entity\Trait\UuidTraitEntity;
use App\Repository\LarpInvitationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: LarpInvitationRepository::class)]
class LarpInvitation
{
    use UuidTraitEntity;
    use TimestampableEntity;

    #[ORM\ManyToOne(targetEntity: Larp::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Larp $larp = null;

    #[ORM\Column(type: 'string', length: 64)]
    private ?string $code = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: false)]
    private ?\DateTimeImmutable $validTo = null;

    #[ORM\Column(enumType: UserRole::class)]
    private UserRole $invitedRole;

    #[ORM\ManyToOne(targetEntity: LarpCharacter::class)]
    private ?LarpCharacter $larpCharacter = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isReusable = true;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['jsonb' => true])]
    private ?array $acceptedByUserIds = [];

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->code = bin2hex(random_bytes(16));
    }


    public function isReusable(): bool
    {
        return $this->isReusable;
    }

    public function setIsReusable(bool $isReusable): self
    {
        $this->isReusable = $isReusable;
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

    public function getAcceptedByUserIds(): ?array
    {
        return $this->acceptedByUserIds;
    }

    public function setAcceptedByUserIds(?array $acceptedByUserIds = []): self
    {
        $this->acceptedByUserIds = $acceptedByUserIds;
        return $this;
    }

    public function addAcceptedByUserId(string $userId): self
    {
        if (!in_array($userId, $this->acceptedByUserIds, true)) {
            $this->acceptedByUserIds[] = $userId;
        }

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getValidTo(): ?\DateTimeImmutable
    {
        return $this->validTo;
    }

    public function setValidTo(?\DateTimeImmutable $validTo = null): self
    {
        $this->validTo = $validTo;
        return $this;
    }

    public function getLarpCharacter(): ?LarpCharacter
    {
        return $this->larpCharacter;
    }

    public function setLarpCharacter(?LarpCharacter $larpCharacter): self
    {
        $this->larpCharacter = $larpCharacter;
        return $this;
    }

    public function getInvitedRole(): UserRole
    {
        return $this->invitedRole;
    }

    public function setInvitedRole(UserRole $invitedRole): void
    {
        $this->invitedRole = $invitedRole;
    }

}
